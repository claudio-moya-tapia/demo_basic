<?php
/**
 * File Template.class.php
 * @package Template
 */

class Template {

    /**
     * Base path for relative file names of subtemplates (for the $Include command).
     * This path is prepended to the subtemplate file names. It must be set before
     * readTemplateFromFile or setTemplateString.
     * @access public
     */
    public $subtemplateBasePath;

    /**#@+
* @access private
     */

    private $maxNestingLevel = 50;            // maximum number of block nestings
    private $maxInclTemplateSize = 1000000;   // maximum length of template string when including subtemplates
    private $template;                        // Template file data
    private $varTab;                          // variables table, array index is variable no
    // Fields:
    //  varName                       // variable name
    //  varValue                      // variable value
    private $varTabCnt;                       // no of entries used in VarTab
    private $varNameToNoMap;                  // maps variable names to variable numbers
    private $varRefTab;                       // variable references table
    // Contains an entry for each variable reference in the template. Ordered by TemplatePos.
    // Fields:
    //  varNo                         // variable no
    //  tPosBegin                     // template position of begin of variable reference
    //  tPosEnd                       // template position of end of variable reference
    //  blockNo                       // block no of the (innermost) block that contains this variable reference
    //  blockVarNo                    // block variable no. Index into BlockInstTab.BlockVarTab
    private $varRefTabCnt;                    // no of entries used in VarRefTab
    private $blockTab;                        // Blocks table, array index is block no
    // Contains an entry for each block in the template. Ordered by TPosBegin.
    // Fields:
    //  blockName                     // block name
    //  nextWithSameName;             // block no of next block with same name or -1 (blocks are backward linked in relation to template position)
    //  tPosBegin                     // template position of begin of block
    //  tPosContentsBegin             // template pos of begin of block contents
    //  tPosContentsEnd               // template pos of end of block contents
    //  tPosEnd                       // template position of end of block
    //  nestingLevel                  // block nesting level
    //  parentBlockNo                 // block no of parent block
    //  definitionIsOpen              // true while $BeginBlock processed but no $EndBlock
    //  instances                     // number of instances of this block
    //  firstBlockInstNo              // block instance no of first instance of this block or -1
    //  lastBlockInstNo               // block instance no of last instance of this block or -1
    //  currBlockInstNo               // current block instance no, used during generation of output file
    //  blockVarCnt                   // no of variables in block
    //  blockVarNoToVarNoMap          // maps block variable numbers to variable numbers
    //  firstVarRefNo                 // variable reference no of first variable of this block or -1
    private $blockTabCnt;                     // no of entries used in BlockTab
    private $blockNameToNoMap;                // maps block names to block numbers
    private $openBlocksTab;
    // During parsing, this table contains the block numbers of the open parent blocks (nested outer blocks).
    // Indexed by the block nesting level.
    private $blockInstTab;                    // block instances table
    // This table contains an entry for each block instance that has been added.
    // Indexed by BlockInstNo.
    // Fields:
    //  blockNo                       // block number
    //  instanceLevel                 // instance level of this block
    //     InstanceLevel is an instance counter per block.
    //     (In contrast to blockInstNo, which is an instance counter over the instances of all blocks)
    //  parentInstLevel               // instance level of parent block
    //  nextBlockInstNo               // pointer to next instance of this block or -1
    //     Forward chain for instances of same block.
    //  blockVarTab                   // block instance variables
    private $blockInstTabCnt;                 // no of entries used in BlockInstTab

    private $currentNestingLevel;             // Current block nesting level during parsing.
    private $templateValid;                   // true if a valid template is prepared
    private $outputMode;                      // 0 = to PHP output stream, 1 = to file, 2 = to string
    private $outputFileHandle;                // file handle during writing of output file
    private $outputError;                     // true when an output error occurred
    private $outputString;                    // string buffer for the generated HTML page

    /* Other class collaboration */
    private $javaScriptSrc;
    /**#@-*/

//--- constructor ---------------------------------------------------------------------------------------------------

    /**
     * Constructs a MiniTemplator object.
     * @access public
     * @param  string   $workspace  workspace folder.
     * @param  string   $module  module folder.
     * @param  string   $item  item folder.
     */
    public function  __construct() {
        $this->templateValid = false;
    }

   
//--- template string handling --------------------------------------------------------------------------------------

    /**
     * Reads the template from a file.
     * @param  string   $fileName  name of the file that contains the template.
     * @return boolean  true on success, false on error.
     * @access public
     */
    public function readTemplateFromFile($fileName) {
        $this->templateName = $fileName;
        if (!$this->readFileIntoString($fileName,$s)) {
            $this->triggerError ("Error while reading template file " . $fileName . ".");
            return false;
        }
        if (!$this->setTemplateString($s)) return false;
        return true;
    }

    /**
     * Assigns a new template string.
     * @param  string   $templateString  contents of the template file.
     * @return boolean  true on success, false on error.
     * @access public
     */
    public function setTemplateString ($templateString) {
        $this->templateValid = false;
        $this->template = $templateString;
        if (!$this->parseTemplate()) return false;
        $this->reset();
        $this->templateValid = true;
        return true;
    }

    /**
     * Loads the template string for a subtemplate (used for the $Include command).
     * @return boolean  true on success, false on error.
     * @access private
     */
    private function loadSubtemplate ($subtemplateName, &$s) {
        $subtemplateFileName = $this->combineFileSystemPath($this->subtemplateBasePath,$subtemplateName);
        if (!$this->readFileIntoString($subtemplateFileName,$s)) {
            $this->triggerError ("Error while reading subtemplate file " . $subtemplateFileName . ".");
            return false;
        }
        return true;
    }

//--- template parsing ----------------------------------------------------------------------------------------------

    /**
     * Parses the template.
     * @return boolean  true on success, false on error.
     * @access private
     */
    private function parseTemplate() {
        $this->initParsing();
        $this->beginMainBlock();
        if (!$this->parseTemplateCommands()) return false;
        $this->endMainBlock();
        if (!$this->checkBlockDefinitionsComplete()) return false;
        if (!$this->parseTemplateVariables()) return false;
        $this->associateVariablesWithBlocks();
        return true;
    }

    /**
     * @access private
     */
    private function initParsing() {
        $this->varTab = array();
        $this->varTabCnt = 0;
        $this->varNameToNoMap = array();
        $this->varRefTab = array();
        $this->varRefTabCnt = 0;
        $this->blockTab = array();
        $this->blockTabCnt = 0;
        $this->blockNameToNoMap = array();
        $this->openBlocksTab = array();
    }

    /**
     * Registers the main block.
     * The main block is an implicitly defined block that covers the whole template.
     * @access private
     */
    private function beginMainBlock() {
        $blockNo = 0;
        $this->registerBlock('@@InternalMainBlock@@', $blockNo);
        $bte =& $this->blockTab[$blockNo];
        $bte['tPosBegin'] = 0;
        $bte['tPosContentsBegin'] = 0;
        $bte['nestingLevel'] = 0;
        $bte['parentBlockNo'] = -1;
        $bte['definitionIsOpen'] = true;
        $this->openBlocksTab[0] = $blockNo;
        $this->currentNestingLevel = 1;
    }

    /**
     * Completes the main block registration.
     * @access private
     */
    private function endMainBlock() {
        $bte =& $this->blockTab[0];
        $bte['tPosContentsEnd'] = strlen($this->template);
        $bte['tPosEnd'] = strlen($this->template);
        $bte['definitionIsOpen'] = false;
        $this->currentNestingLevel -= 1;
    }

    /**
     * Parses commands within the template in the format "<!-- $command parameters -->".
     * @return boolean  true on success, false on error.
     * @access private
     */
    private function parseTemplateCommands() {
        $p = 0;
        while (true) {
            $p0 = strpos($this->template,'<!--',$p);
            if ($p0 === false) break;
            $p = strpos($this->template,'-->',$p0);
            if ($p === false) {
                $this->triggerError ("Invalid HTML comment in template at offset $p0.");
                return false;
            }
            $p += 3;
            $cmdL = substr($this->template,$p0+4,$p-$p0-7);
            if (!$this->processTemplateCommand($cmdL,$p0,$p,$resumeFromStart))
                return false;
            if ($resumeFromStart) $p = $p0;
        }
        return true;
    }

    /**
     * @return boolean  true on success, false on error.
     * @access private
     */
    private function processTemplateCommand ($cmdL, $cmdTPosBegin, $cmdTPosEnd, &$resumeFromStart) {
        $resumeFromStart = false;
        $p = 0;
        $cmd = '';
        if (!$this->parseWord($cmdL,$p,$cmd)) return true;
        $parms = substr($cmdL,$p);
        switch (strtoupper($cmd)) {
            case '$BEGINBLOCK':
                if (!$this->processBeginBlockCmd($parms,$cmdTPosBegin,$cmdTPosEnd))
                    return false;
                break;
            case '$ENDBLOCK':
                if (!$this->processEndBlockCmd($parms,$cmdTPosBegin,$cmdTPosEnd))
                    return false;
                break;
            case '$INCLUDE':
                if (!$this->processincludeCmd($parms,$cmdTPosBegin,$cmdTPosEnd))
                    return false;
                $resumeFromStart = true;
                break;
            default:
                if ($cmd{0} == '$' && !(strlen($cmd) >= 2 && $cmd{1} == '{')) {
                    $this->triggerError ("Unknown command \"$cmd\" in template at offset $cmdTPosBegin.");
                    return false;
                }
        }

        return true;
    }

    /**
     * Processes the $BeginBlock command.
     * @return boolean  true on success, false on error.
     * @access private
     */
    private function processBeginBlockCmd ($parms, $cmdTPosBegin, $cmdTPosEnd) {
        $p = 0;
        if (!$this->parseWord($parms,$p,$blockName)) {
            $this->triggerError ("Missing block name in \$BeginBlock command in template at offset $cmdTPosBegin.");
            return false;
        }
        if (trim(substr($parms,$p)) != '') {
            $this->triggerError ("Extra parameter in \$BeginBlock command in template at offset $cmdTPosBegin.");
            return false;
        }
        $this->registerBlock ($blockName, $blockNo);
        $btr =& $this->blockTab[$blockNo];
        $btr['tPosBegin'] = $cmdTPosBegin;
        $btr['tPosContentsBegin'] = $cmdTPosEnd;
        $btr['nestingLevel'] = $this->currentNestingLevel;
        $btr['parentBlockNo'] = $this->openBlocksTab[$this->currentNestingLevel-1];
        $this->openBlocksTab[$this->currentNestingLevel] = $blockNo;
        $this->currentNestingLevel += 1;
        if ($this->currentNestingLevel > $this->maxNestingLevel) {
            $trhis->triggerError ("Block nesting overflow in template at offset $cmdTPosBegin.");
            return false;
        }
        return true;
    }

    /**
     * Processes the $EndBlock command.
     * @return boolean  true on success, false on error.
     * @access private
     */
    private function processEndBlockCmd ($parms, $cmdTPosBegin, $cmdTPosEnd) {
        $p = 0;
        if (!$this->parseWord($parms,$p,$blockName)) {
            $this->triggerError ("Missing block name in \$EndBlock command in template at offset $cmdTPosBegin.");
            return false;
        }
        if (trim(substr($parms,$p)) != '') {
            $this->triggerError ("Extra parameter in \$EndBlock command in template at offset $cmdTPosBegin.");
            return false;
        }
        if (!$this->lookupBlockName($blockName,$blockNo)) {
            $this->triggerError ("Undefined block name \"$blockName\" in \$EndBlock command in template at offset $cmdTPosBegin.");
            return false;
        }
        $this->currentNestingLevel -= 1;
        $btr =& $this->blockTab[$blockNo];
        if (!$btr['definitionIsOpen']) {
            $this->triggerError ("Multiple \$EndBlock command for block \"$blockName\" in template at offset $cmdTPosBegin.");
            return false;
        }
        if ($btr['nestingLevel'] != $this->currentNestingLevel) {
            $this->triggerError ("Block nesting level mismatch at \$EndBlock command for block \"$blockName\" in template at offset $cmdTPosBegin.");
            return false;
        }
        $btr['tPosContentsEnd'] = $cmdTPosBegin;
        $btr['tPosEnd'] = $cmdTPosEnd;
        $btr['definitionIsOpen'] = false;
        return true;
    }

    /**
     * @access private
     */
    private function registerBlock($blockName, &$blockNo) {
        $blockNo = $this->blockTabCnt++;
        $btr =& $this->blockTab[$blockNo];
        $btr = array();
        $btr['blockName'] = $blockName;
        if (!$this->lookupBlockName($blockName,$btr['nextWithSameName']))
            $btr['nextWithSameName'] = -1;
        $btr['definitionIsOpen'] = true;
        $btr['instances'] = 0;
        $btr['firstBlockInstNo'] = -1;
        $btr['lastBlockInstNo'] = -1;
        $btr['blockVarCnt'] = 0;
        $btr['firstVarRefNo'] = -1;
        $btr['blockVarNoToVarNoMap'] = array();
        $this->blockNameToNoMap[strtoupper($blockName)] = $blockNo;
    }

    /**
     * Checks that all block definitions are closed.
     * @return boolean  true on success, false on error.
     * @access private
     */
    private function checkBlockDefinitionsComplete() {
        for ($blockNo=0; $blockNo < $this->blockTabCnt; $blockNo++) {
            $btr =& $this->blockTab[$blockNo];
            if ($btr['definitionIsOpen']) {
                $this->triggerError ("Missing \$EndBlock command in template for block " . $btr['blockName'] . ".");
                return false;
            }
        }

        if ($this->currentNestingLevel != 0) {
            $this->triggerError ("Block nesting level error at end of template.");
            return false;
        }
        return true;
    }

    /**
     * Processes the $Include command.
     * @return boolean  true on success, false on error.
     * @access private
     */
    private function processIncludeCmd($parms, $cmdTPosBegin, $cmdTPosEnd) {
        $p = 0;
        if (!$this->parseWordOrQuotedString($parms,$p,$subtemplateName)) {
            $this->triggerError ("Missing or invalid subtemplate name in \$Include command in template at offset $cmdTPosBegin.");
            return false;
        }
        if (trim(substr($parms,$p)) != '') {
            $this->triggerError ("Extra parameter in \$include command in template at offset $cmdTPosBegin.");
            return false;
        }
        return $this->insertSubtemplate($subtemplateName,$cmdTPosBegin,$cmdTPosEnd);
    }

    /**
     * Processes the $Include command.
     * @return boolean  true on success, false on error.
     * @access private
     */
    private function insertSubtemplate($subtemplateName, $tPos1, $tPos2) {
        if (strlen($this->template) > $this->maxInclTemplateSize) {
            $this->triggerError ("Subtemplate include aborted because the internal template string is longer than $this->maxInclTemplateSize characters.");
            return false;
        }
        if (!$this->loadSubtemplate($subtemplateName,$subtemplate)) return false;
        // (Copying the template to insert a subtemplate is a bit slow. In a future implementation of MiniTemplator,
        // a table could be used that contains references to the string fragments.)
        $this->template = substr($this->template,0,$tPos1) . $subtemplate . substr($this->template,$tPos2);
        return true;
    }

    /**
     * Parses variable references within the template in the format "${VarName}".
     * @return boolean  true on success, false on error.
     * @access private
     */
    private function parseTemplateVariables() {
        $p = 0;
        while (true) {
            $p = strpos($this->template, '${', $p);
            if ($p === false) break;
            $p0 = $p;
            $p = strpos($this->template, '}', $p);
            if ($p === false) {
                $this->triggerError ("Invalid variable reference in template at offset $p0.");
                return false;
            }
            $p += 1;
            $varName = trim(substr($this->template, $p0+2, $p-$p0-3));
            if (strlen($varName) == 0) {
                $this->triggerError ("Empty variable name in template at offset $p0.");
                return false;
            }
            $this->registerVariableReference ($varName, $p0, $p);
        }
        return true;
    }

    /**
     * @access private
     */
    private function registerVariableReference ($varName, $tPosBegin, $tPosEnd) {
        if (!$this->lookupVariableName($varName,$varNo))
            $this->registerVariable($varName,$varNo);
        $varRefNo = $this->varRefTabCnt++;
        $vrtr =& $this->varRefTab[$varRefNo];
        $vrtr = array();
        $vrtr['tPosBegin'] = $tPosBegin;
        $vrtr['tPosEnd'] = $tPosEnd;
        $vrtr['varNo'] = $varNo;
    }

    /**
     * @access private
     */
    private function registerVariable ($varName, &$varNo) {
        $varNo = $this->varTabCnt++;
        $vtr =& $this->varTab[$varNo];
        $vtr = array();
        $vtr['varName'] = $varName;
        $vtr['varValue'] = '';
        $this->varNameToNoMap[strtoupper($varName)] = $varNo;
    }

    /**
     * Associates variable references with blocks.
     * @access private
     */
    private function associateVariablesWithBlocks() {
        $varRefNo = 0;
        $activeBlockNo = 0;
        $nextBlockNo = 1;
        while ($varRefNo < $this->varRefTabCnt) {
            $vrtr =& $this->varRefTab[$varRefNo];
            $varRefTPos = $vrtr['tPosBegin'];
            $varNo = $vrtr['varNo'];
            if ($varRefTPos >= $this->blockTab[$activeBlockNo]['tPosEnd']) {
                $activeBlockNo = $this->blockTab[$activeBlockNo]['parentBlockNo'];
                continue;
            }
            if ($nextBlockNo < $this->blockTabCnt) {
                if ($varRefTPos >= $this->blockTab[$nextBlockNo]['tPosBegin']) {
                    $activeBlockNo = $nextBlockNo;
                    $nextBlockNo += 1;
                    continue;
                }
            }

            $btr =& $this->blockTab[$activeBlockNo];
            if ($varRefTPos < $btr['tPosBegin'])
                $this->programLogicError(1);
            $blockVarNo = $btr['blockVarCnt']++;
            $btr['blockVarNoToVarNoMap'][$blockVarNo] = $varNo;
            if ($btr['firstVarRefNo'] == -1)
                $btr['firstVarRefNo'] = $varRefNo;
            $vrtr['blockNo'] = $activeBlockNo;
            $vrtr['blockVarNo'] = $blockVarNo;
            $varRefNo += 1;
        }
    }


//--- build up (template variables and blocks) ----------------------------------------------------------------------

    /**
     * Clears all variables and blocks.
     * This method can be used to produce another HTML page with the same
     * template. It is faster than creating another MiniTemplator object,
     * because the template does not have to be parsed again.
     * All variable values are cleared and all added block instances are deleted.
     * @access public
     */
    public function reset() {
        for ($varNo=0; $varNo<$this->varTabCnt; $varNo++)
            $this->varTab[$varNo]['varValue'] = '';
        for ($blockNo=0; $blockNo<$this->blockTabCnt; $blockNo++) {
            $btr =& $this->blockTab[$blockNo];
            $btr['instances'] = 0;
            $btr['firstBlockInstNo'] = -1;
            $btr['lastBlockInstNo'] = -1;
        }
        $this->blockInstTab = array();
        $this->blockInstTabCnt = 0;
    }

    /**
     * Sets a template variable.
     * For variables that are used in blocks, the variable value
     * must be set before {@link addBlock} is called.
     * @param  string  $variableName   the name of the variable to be set.
     * @param  string  $variableValue  the new value of the variable.
     * @param  boolean $isOptional     Specifies whether an error should be
     *    generated when the variable does not exist in the template. If
     *    $isOptional is false and the variable does not exist, an error is
     *    generated.
     * @return boolean true on success, or false on error (e.g. when no
     *    variable with the specified name exists in the template and
     *    $isOptional is false).
     * @access public
     */
    public function setVariable($variableName, $variableValue, $isOptional=false) {
        $isOptional = true; //forzar variable opcional

        if (!$this->templateValid) {
            $this->triggerError ("Template not valid.");
            return false;
        }
        if (!$this->lookupVariableName($variableName,$varNo)) {
            if ($isOptional) return true;
            $this->triggerError ("Variable \"$variableName\" not defined in template.");
            return false;
        }
        $this->varTab[$varNo]['varValue'] = $variableValue;
        return true;
    }

     /*
     * Element handlers
     */

    /**
     * Sets a template variable.
     * @param  Element  $Element
     * @access public
     */
    public function addElement($Element){
        $this->setVariable($Element->getTemplate(), $Element->getDisplay());
    }

    /**
     * Sets a template variable.
     * @param  string   $listName
     * @param  Element  $Element
     * @access public
     */
    public function parseList($listName,$Element){
        $this->setVariable($listName, $Element->getDisplay());
        unset($listName,$Element);
    }

    /**
     * Sets a template variable.
     * @access public
     */
    public function addJavaScript($aryJavaScript){
        $script = '';
        foreach($aryJavaScript as $js){
            $script .= '
        <script language="javascript" type="text/javascript" src="'.JavaScript::get($js).'"></script>';
        }

        $this->setVariable('JavaScript', $script);
        unset($aryJavaScript,$script,$js);
    }

    /**
     * Sets a template variable.
     * @access public
     */
    public function addCss($aryCss){
        $link = '';
        foreach($aryCss as $Css){
            $link .= '
        <link type="text/css" rel="stylesheet" href="'.Css::get($Css).'" />';
        }

       $this->setVariable('Css', $link);
       unset($aryCss,$link,$Css);
    }


    /**
     * Sets a template variable.
     * @param  string   $listName
     * @param  string  $value
     * @access public
     */
    public function parse($name,$value){
        $this->setVariable($name, $value);
        unset($name,$value);
    }


   
    /**
     * Sets a template variable to an escaped string.
     * This method is identical to (@link setVariable), except that
     * the characters &lt;, &gt;, &amp;, ' and " of variableValue are
     * replaced by their corresponding HTML/XML character entity codes.
     * For variables that are used in blocks, the variable value
     * must be set before {@link addBlock} is called.
     * @param  string  $variableName   the name of the variable to be set.
     * @param  string  $variableValue  the new value of the variable. Special HTML/XML characters are escaped.
     * @param  boolean $isOptional     Specifies whether an error should be
     *    generated when the variable does not exist in the template. If
     *    $isOptional is false and the variable does not exist, an error is
     *    generated.
     * @return boolean true on success, or false on error (e.g. when no
     *    variable with the specified name exists in the template and
     *    $isOptional is false).
     * @access public
     */
    public function setVariableEsc ($variableName, $variableValue, $isOptional=false) {
        return $this->setVariable($variableName,htmlspecialchars($variableValue,ENT_QUOTES),$isOptional);
    }

    /**
     * Checks whether a variable with the specified name exists within the template.
     * @param  string  $variableName   the name of the variable.
     * @return boolean true if the variable exists, or false when no
     *    variable with the specified name exists in the template.
     * @access public
     */
    public function variableExists ($variableName) {
        if (!$this->templateValid) {
            $this->triggerError ("Template not valid.");
            return false;
        }
        return $this->lookupVariableName($variableName,$varNo);
    }

    /**
     * Adds an instance of a template block.
     * If the block contains variables, these variables must be set
     * before the block is added.
     * If the block contains subblocks (nested blocks), the subblocks
     * must be added before this block is added.
     * If multiple blocks exist with the specified name, an instance
     * is added for each block occurence.
     * @param  string   blockName the name of the block to be added.
     * @return boolean  true on success, false on error (e.g. when no
     *    block with the specified name exists in the template).
     * @access public
     */
    public function addBlock($blockName) {
        if (!$this->templateValid) {
            $this->triggerError ("Template not valid.");
            return false;
        }
        if (!$this->lookupBlockName($blockName,$blockNo)) {
            $this->triggerError ("Block \"$blockName\" not defined in template.");
            return false;
        }
        while ($blockNo != -1) {
            $this->addBlockByNo($blockNo);
            $blockNo = $this->blockTab[$blockNo]['nextWithSameName'];
        }
        return true;
    }

    /**
     * @access private
     */
    private function addBlockByNo ($blockNo) {
        $btr =& $this->blockTab[$blockNo];
        $this->registerBlockInstance ($blockInstNo);
        $bitr =& $this->blockInstTab[$blockInstNo];
        if ($btr['firstBlockInstNo'] == -1)
            $btr['firstBlockInstNo'] = $blockInstNo;
        if ($btr['lastBlockInstNo'] != -1)
            $this->blockInstTab[$btr['lastBlockInstNo']]['nextBlockInstNo'] = $blockInstNo;
        // set forward pointer of chain
        $btr['lastBlockInstNo'] = $blockInstNo;
        $parentBlockNo = $btr['parentBlockNo'];
        $blockVarCnt = $btr['blockVarCnt'];
        $bitr['blockNo'] = $blockNo;
        $bitr['instanceLevel'] = $btr['instances']++;
        if ($parentBlockNo == -1)
            $bitr['parentInstLevel'] = -1;
        else
            $bitr['parentInstLevel'] = $this->blockTab[$parentBlockNo]['instances'];
        $bitr['nextBlockInstNo'] = -1;
        $bitr['blockVarTab'] = array();
        // copy instance variables for this block
        for ($blockVarNo=0; $blockVarNo<$blockVarCnt; $blockVarNo++) {
            $varNo = $btr['blockVarNoToVarNoMap'][$blockVarNo];
            $bitr['blockVarTab'][$blockVarNo] = $this->varTab[$varNo]['varValue'];
        }
    }


    /**
     * @access private
     */
    private function registerBlockInstance (&$blockInstNo) {
        $blockInstNo = $this->blockInstTabCnt++;
    }

    /**
     * Checks whether a block with the specified name exists within the template.
     * @param  string  $blockName   the name of the block.
     * @return boolean true if the block exists, or false when no
     *    block with the specified name exists in the template.
     * @access public
     */
    public function blockExists ($blockName) {
        if (!$this->templateValid) {
            $this->triggerError ("Template not valid.");
            return false;
        }
        return $this->lookupBlockName($blockName,$blockNo);
    }

    /**
     * Alias for generateOutputToString, generateOutput
     * @access public
     * @return boolean  true on success, false on error.
     */
    public function display() {
        return $this->generateOutput();
    }
    /**
     * Alias for generateOutputToString, generateOutput
     * @access public
     * @return string parsed template.
     */
    public function getDisplay() {
        $this->generateOutputToString($outputString);
        return $outputString;
    }
   
    /**
     * Generates the HTML page and writes it to the PHP output stream.
     * @return boolean  true on success, false on error.
     * @access public
     */
    public function generateOutput () {
        $this->outputMode = 0;
        if (!$this->generateOutputPage()) return false;
        return true;
    }

    /**
     * Generates the HTML page and writes it to a file.
     * @param  string   $fileName  name of the output file.
     * @return boolean  true on success, false on error.
     * @access public
     */
    public function generateOutputToFile ($fileName) {
        $fh = fopen($fileName,"wb");
        if ($fh === false) return false;
        $this->outputMode = 1;
        $this->outputFileHandle = $fh;
        $ok = $this->generateOutputPage();
        fclose ($fh);
        return $ok;
    }


    /**
     * Generates the HTML page and writes it to a string.
     * @param  string   $outputString  variable that receives
     *                  the contents of the generated HTML page.
     * @return boolean  true on success, false on error.
     * @access public
     */
    public function generateOutputToString (&$outputString) {
        $outputString = "Error";
        $this->outputMode = 2;
        $this->outputString = "";
        if (!$this->generateOutputPage()) return false;
        $outputString = $this->outputString;
        return true;
    }

    /**
     * @access private
     * @return boolean  true on success, false on error.
     */
    private function generateOutputPage() {
        if (!$this->templateValid) {
            $this->triggerError ("Template not valid.");
            return false;
        }
        if ($this->blockTab[0]['instances'] == 0)
            $this->addBlockByNo (0);        // add main block
        for ($blockNo=0; $blockNo < $this->blockTabCnt; $blockNo++) {
            $btr =& $this->blockTab[$blockNo];
            $btr['currBlockInstNo'] = $btr['firstBlockInstNo'];
        }
        $this->outputError = false;
        $this->writeBlockInstances (0, -1);
        if ($this->outputError) return false;
        return true;
    }

    /**
     * Writes all instances of a block that are contained within a specific
     * parent block instance.
     * Called recursively.
     * @access private
     */
    private function writeBlockInstances ($blockNo, $parentInstLevel) {
        $btr =& $this->blockTab[$blockNo];
        while (!$this->outputError) {
            $blockInstNo = $btr['currBlockInstNo'];
            if ($blockInstNo == -1) break;
            $bitr =& $this->blockInstTab[$blockInstNo];
            if ($bitr['parentInstLevel'] < $parentInstLevel)
                $this->programLogicError (2);
            if ($bitr['parentInstLevel'] > $parentInstLevel) break;
            $this->writeBlockInstance ($blockInstNo);
            $btr['currBlockInstNo'] = $bitr['nextBlockInstNo'];
        }
    }


    /**
     * @access private
     */
    private function writeBlockInstance($blockInstNo) {
        $bitr =& $this->blockInstTab[$blockInstNo];
        $blockNo = $bitr['blockNo'];
        $btr =& $this->blockTab[$blockNo];
        $tPos = $btr['tPosContentsBegin'];
        $subBlockNo = $blockNo + 1;
        $varRefNo = $btr['firstVarRefNo'];
        while (!$this->outputError) {
            $tPos2 = $btr['tPosContentsEnd'];
            $kind = 0;                                // assume end-of-block
            if ($varRefNo != -1 && $varRefNo < $this->varRefTabCnt) {  // check for variable reference
                $vrtr =& $this->varRefTab[$varRefNo];
                if ($vrtr['tPosBegin'] < $tPos) {
                    $varRefNo += 1;
                    continue;
                }
                if ($vrtr['tPosBegin'] < $tPos2) {
                    $tPos2 = $vrtr['tPosBegin'];
                    $kind = 1;
                }
            }

            if ($subBlockNo < $this->blockTabCnt) {   // check for subblock
                $subBtr =& $this->blockTab[$subBlockNo];
                if ($subBtr['tPosBegin'] < $tPos) {
                    $subBlockNo += 1;
                    continue;
                }
                if ($subBtr['tPosBegin'] < $tPos2) {
                    $tPos2 = $subBtr['tPosBegin'];
                    $kind = 2;
                }
            }

            if ($tPos2 > $tPos)
                $this->writeString (substr($this->template,$tPos,$tPos2-$tPos));
            switch ($kind) {
                case 0:         // end of block
                    return;
                case 1:         // variable
                    $vrtr =& $this->varRefTab[$varRefNo];
                    if ($vrtr['blockNo'] != $blockNo)
                        $this->programLogicError (4);
                    $variableValue = $bitr['blockVarTab'][$vrtr['blockVarNo']];
                    $this->writeString ($variableValue);
                    $tPos = $vrtr['tPosEnd'];
                    $varRefNo += 1;
                    break;
                case 2:         // sub block
                    $subBtr =& $this->blockTab[$subBlockNo];
                    if ($subBtr['parentBlockNo'] != $blockNo)
                        $this->programLogicError (3);
                    $this->writeBlockInstances ($subBlockNo, $bitr['instanceLevel']);  // recursive call
                    $tPos = $subBtr['tPosEnd'];
                    $subBlockNo += 1;
                    break;
            }
        }

    }

    /**
     * @access private
     */
    private function writeString ($s) {
        if ($this->outputError) return;
        switch ($this->outputMode) {
            case 0:            // output to PHP output stream
                if (!print($s))
                    $this->outputError = true;
                break;
            case 1:            // output to file
                $rc = fwrite($this->outputFileHandle, $s);
                if ($rc === false) $this->outputError = true;
                break;
            case 2:            // output to string
                $this->outputString .= $s;
                break;
        }
    }

    /**
     * Maps variable name to variable number.
     * @return boolean  true on success, false if the variable is not found.
     * @access private
     */
    private function lookupVariableName ($varName, &$varNo) {
        $x =& $this->varNameToNoMap[strtoupper($varName)];
        if (!isset($x)) return false;
        $varNo = $x;
        return true;
    }

    /**
     * Maps block name to block number.
     * If there are multiple blocks with the same name, the block number of the last
     * registered block with that name is returned.
     * @return boolean  true on success, false when the block is not found.
     * @access private
     */
    private function lookupBlockName ($blockName, &$blockNo) {
        $x =& $this->blockNameToNoMap[strtoupper($blockName)];
        if (!isset($x)) return false;
        $blockNo = $x;
        return true;
    }

    /**
     * Reads a file into a string.
     * @return boolean  true on success, false on error.
     * @access private
     */
    private function readFileIntoString ($fileName, &$s) {
        if (function_exists('version_compare') && version_compare(phpversion(),"4.3.0",">=")) {
            $s = file_get_contents($fileName);
            if ($s === false) return false;
            return true;
        }
        $fh = fopen($fileName,"rb");
        if ($fh === false) return false;
        $fileSize = filesize($fileName);
        if ($fileSize === false) {
            close ($fh);
            return false;
        }
        $s = fread($fh,$fileSize);
        fclose ($fh);
        if (strlen($s) != $fileSize) return false;
        return true;
    }

    /**
     * @access private
     * @return boolean  true on success, false when the end of the string is reached.
     */
    private function parseWord ($s, &$p, &$w) {
        $sLen = strlen($s);
        while ($p < $sLen && ord($s{$p}) <= 32) $p++;
        if ($p >= $sLen) return false;
        $p0 = $p;
        while ($p < $sLen && ord($s{$p}) > 32) $p++;
        $w = substr($s, $p0, $p - $p0);
        return true;
    }

    /**
     * @access private
     * @return boolean  true on success, false on error.
     */
    private function parseQuotedString ($s, &$p, &$w) {
        $sLen = strlen($s);
        while ($p < $sLen && ord($s{$p}) <= 32) $p++;
        if ($p >= $sLen) return false;
        if (substr($s,$p,1) != '"') return false;
        $p++;
        $p0 = $p;
        while ($p < $sLen && $s{$p} != '"') $p++;
        if ($p >= $sLen) return false;
        $w = substr($s, $p0, $p - $p0);
        $p++;
        return true;
    }

    /**
     * @access private
     * @return boolean  true on success, false on error.
     */
    private function parseWordOrQuotedString ($s, &$p, &$w) {
        $sLen = strlen($s);
        while ($p < $sLen && ord($s{$p}) <= 32) $p++;
        if ($p >= $sLen) return false;
        if (substr($s,$p,1) == '"')
            return $this->parseQuotedString($s,$p,$w);
        else
            return $this->parseWord($s,$p,$w);
    }

    /**
     * Combine two file system paths.
     * @access private
     */
    private function combineFileSystemPath ($path1, $path2) {
        if ($path1 == '' || $path2 == '') return $path2;
        $s = $path1;
        if (substr($s,-1) != '\\' && substr($s,-1) != '/') $s = $s . "/";
        if (substr($path2,0,1) == '\\' || substr($path2,0,1) == '/')
            $s = $s . substr($path2,1);
        else
            $s = $s . $path2;
        return $s;
    }

    /**
     * @access private
     */
    private function triggerError ($msg) {
        trigger_error ("MiniTemplator error: $msg", E_USER_ERROR);
    }

    /**
     * @access private
     */
    private function programLogicError ($errorId) {
        die ("MiniTemplator: Program logic error $errorId.\n");
    }


    public function __destruct() {

    }

}
?>