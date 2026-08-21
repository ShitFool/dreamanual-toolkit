jQuery(function($){
    if(typeof inlineEditPost==='undefined') return;
    $(document).on('click','.editinline',function(){
        var $row=$(this).closest('tr');
        var $editRow=$('#edit-'+$row.attr('id').replace('post-',''));
        var excerpt=$row.find('.drea-quickedit-excerpt-data').data('excerpt')||'';
        setTimeout(function(){
            $editRow.find('textarea[name="excerpt"]').val(excerpt);
        },50);
    });
});
