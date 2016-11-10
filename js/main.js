$(document).ready(function(){
    
    $('#myTabs a').click(function (e) {
        e.preventDefault()
        $(this).tab('show')
    })

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
}) 

