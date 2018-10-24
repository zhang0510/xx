<html>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=0.6,user-scalable=no">
<style>
    body,html{height:100%;width:100%;overflow:hidden;font-size: 24px;background-color: #fafafa;}
    select{font-size: 24px;width:36%;height:50px;}
    select option{font-size: 16px;}
</style>
<body>
    <form style="width: 95%;margin: auto;">
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
        <br>
        <br>
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
        <br>
        <br>
        <div style="width:400px;margin: auto">
            <input id="di_price" type="button" value="0" style="width:150px;height:100px;font-size: 48px;border-radius:15px;background-color: #ea5512;color: #ffffff"><span style="font-size: 48px;">￥</span>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <input id="serch" type="button" value="查询" style="width:100px;height:50px;font-size: 24px;border-radius:10px;background-color: #cab4e2;color: #ffffff">
        </div>
        <div style="margin-top:10px;height: 668px;overflow:auto;font-size: 14px;">
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
            if(data.content == ''){
                $('#di_price').val('0');
                $('#line').html('暂无报价');
            }else{
                $('#line').html(data.content);
                $('#di_price').val(data.price);
            }

        },"json")
    })
</script>