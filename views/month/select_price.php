<html>
<body>
    <form action="/month/price_select" method="post">
        出发地：
        <select name="start_prov" class="prov" id="start">
            <option value="">请选择</option>
            <?php foreach($area as $k=>$v){ ?>
                <option value="<?php echo $v['area_id']; ?>"><?php echo $v['area_name']; ?></option>
            <?php } ?>
        </select>
        <select id="start_city">
            <option value="">请选择</option>
        </select>

        目的地：
        <select name="end_prov" class="prov" id="end">
            <option value="">请选择</option>
            <?php foreach($area as $k=>$v){ ?>
                <option value="<?php echo $v['area_id']; ?>"><?php echo $v['area_name']; ?></option>
            <?php } ?>
        </select>
        <select id="end_city">
            <option value="">请选择</option>
        </select>
        <input id="serch" type="button" value="查询">
        <div style="margin-top: 20px;height:200px;">
            <span id="line"></span>
        </div>
    </form>
</body>
</html>
<script src="/js/jquery-1.7.2.min.js"></script>
<script>
    $(document).on('change','.prov',function(){
        id = $(this).val();
        type = $(this).attr('id');
        if(type == 'start'){
            obj = $('#start_city');
        }else{
            obj = $('#end_city');
        }
        $.post('/month/get_city',{'id':id},function(data){
            obj.html(data);
        })
    })

    $(document).on('click','#serch',function(){
        start = $("#start").val();
        start_city = $("#start_city").val();
        end = $("#end").val();
        end_city = $("#end_city").val();
        if(type == 'start'){
            obj = $('#start_city');
        }else{
            obj = $('#end_city');
        }
        $.post('/month/getprice',{'start':start,'start_city':start_city,'end':end,'end_city':end_city},function(data){
            if(data == ''){
                $('#line').html('暂无报价');
            }else{
                $('#line').html(data);
            }

        })
    })
</script>