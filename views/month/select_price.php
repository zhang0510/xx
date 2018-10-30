<html>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=0.6,user-scalable=no">
<style>
    body,html{height:100%;width:100%;overflow:hidden;font-size: 24px;background-color: #fafafa;}
    .prov,.city{font-size: 24px;width:92%;height:50px;text-indent: 10px;}
    ul{margin-top:3px;height:300px;overflow: auto;width:90%;border:1px solid #000000;padding-left: 0px;background-color: #ffffff;position: relative;display: none}
    li{font-size: 24px;list-style-type:none;line-height: 43px;text-indent: 20px;}
</style>
<body>
    <form style="width: 95%;margin: auto;">
        <div style="height:281px;margin-top: 12px;width: 100%;">
            <div style="float:left;width:20%;height: 80px;">
                出发地：
            </div>
            <div style="float:left;width:39%;height: 80px;">
                <input class="prov">
                <ul class="start_ul" id="start_prov">
                    <?php foreach($area as $k=>$v){ ?>
                        <li value="<?php echo $v['area_id']; ?>"><?php echo $v['area_name']; ?></li>
                    <?php } ?>
                </ul>
            </div>
            <div style="float:right;width:40%;height: 80px;">
                <input class="city">
                <ul id="start_city" class="city_ul">
                    <li value="">请选择</li>
                </ul>
            </div>
            <div style="float:left;width:20%;height: 80px;">
                目的地：
            </div>
            <div style="float:left;width:39%;height: 80px;">
                <input class="prov">
                <ul class="start_ul" id="end_prov">
                    <?php foreach($area as $k=>$v){ ?>
                        <li value="<?php echo $v['area_id']; ?>"><?php echo $v['area_name']; ?></li>
                    <?php } ?>
                </ul>
            </div>
            <div style="float:right;width:40%;height: 80px;">
                <input class="city">
                <ul id="end_city" class="city_ul">
                    <li value="">请选择</li>
                </ul>
            </div>
            <br>
            <br>
            <div style="width:400px;margin: auto">
                <input id="di_price" type="button" value="0" style="width:150px;height:100px;font-size: 48px;border-radius:15px;background-color: #ea5512;color: #ffffff"><span style="font-size: 48px;">￥</span>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input id="serch" type="button" value="查询" style="width:100px;height:50px;font-size: 24px;border-radius:10px;background-color: #cab4e2;color: #ffffff">
            </div>
        </div>
        <div id="content" style="margin-top:10px;height: 668px;overflow:auto;font-size: 14px;">
            <span id="line"></span>
        </div>
        <input type="hidden" name="start_prov" value="">
        <input type="hidden" name="end_prov" value="">
        <input type="hidden" name="start_city" value="">
        <input type="hidden" name="end_city" value="">
    </form>
</body>
</html>
<script src="/js/jquery-1.7.2.min.js"></script>
<script>
    height=$(document).height();
    hei = height-330;
    $("#content").css('height',hei);
    $(document).on('focus','.prov,.city',function(){
        $(this).next().show();
    })

    $(document).on('input','.prov',function(){
        name = $(this).val();
        obj = $(this);
        $.post('/month/get_prov',{'name':name},function(data){
            obj.next().html(data);
        })
    })

    $(document).on('input','.city',function(){
        name = $(this).val();
        obj = $(this);
        id_type = $(this).next().attr('id');
        if(id_type == 'start_city'){
            id = $("input[name='start_prov']").val();
        }else{
            id = $("input[name='end_prov']").val();
        }
        $.post('/month/get_city',{'id':id,'name':name},function(data){
            obj.next().html(data);
        })
    })

    $(document).on('click','.start_ul li',function(){
        id = $(this).attr('value');
        name = $(this).html();
        $(this).parent().prev().val(name);
        $(this).parent().hide();
        type = $(this).parent().attr('id');
        if(type == 'start_prov'){
            obj = $('#start_city');
            $("input[name='start_prov']").val(id);
            $("#start_city").prev().val('');
        }else{
            obj = $('#end_city');
            $("input[name='end_prov']").val(id);
            $("#end_city").prev().val('');
        }
        $.post('/month/get_city',{'id':id},function(data){
            obj.html(data);
        })
    })

    $(document).on('click','.city_ul li',function(){
        id = $(this).attr('value');
        name = $(this).html();
        $(this).parent().prev().val(name);
        $(this).parent().hide();
        type = $(this).parent().attr('id');
        $("input[name='"+type+"']").val(id);
    })

    $(document).on('click','#serch',function(){
        start = $("input[name='start_prov']").val();
        start_city = $("input[name='start_city']").val();
        end = $("input[name='end_prov']").val();
        end_city = $("input[name='end_city']").val();
        if(type == 'start'){
            obj = $('#start_city');
        }else{
            obj = $('#end_city');
        }
        $.post('/month/getprice',{'start':start,'start_city':start_city,'end':end,'end_city':end_city},function(data){
            if(data.price == '0'){
                $('#di_price').val('0');
                $('#line').html('<div style="color:red;font-size: 24px;">'+data.content+'</div>');
            }else{
                $('#line').html(data.content);
                $('#di_price').val(data.price);
            }

        },"json")
    })
</script>