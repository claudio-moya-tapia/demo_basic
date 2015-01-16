var Post = {
    
    addMegusta: function(idPost, idUsuario, megustaId) {
                
        $.get(Config.httpBase, { 
            page: 'megusta', 
            action: 'add',
            id: idPost,
            id_usuario: idUsuario
        },function(data) {
           
           if(data == 'ok'){
               $('#'+megustaId).css('background-position','0 -18px');
           }
            
        });
    },
    
    deleteMegusta: function(idPost, idUsuario,megustaId) {
                
        $.get(Config.httpBase, { 
            page: 'megusta', 
            action: 'delete',
            id: idPost,
            id_usuario: idUsuario
        },function(data) {
        
            if(data == 'ok'){
              $('#'+megustaId).css('background-position','0 0px');
           }     
           
        });
    },
    
    addComentario: function(){
        $('#formComentarios').submit();
    }
};