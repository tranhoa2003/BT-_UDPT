<html><head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>Workerman Chat - Phòng Chat PHP Realtime WebSocket</title>
  <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/jquery-sinaEmotion-2.1.0.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
	
  <script type="text/javascript" src="/js/swfobject.js"></script>
  <script type="text/javascript" src="/js/web_socket.js"></script>
  <script type="text/javascript" src="/js/jquery.min.js"></script>
    <script type="text/javascript" src="/js/jquery-sinaEmotion-2.1.0.min.js"></script>
    
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('img/anh.jpg');
            background-size: cover;  /* Ensures the image covers the entire body */
            background-position: center;  /* Centers the image */
            
            color: #333;
        }

        /* Message Input */
        form {
            margin-top: 15px;
        }

        #textarea {
            width: 100%;
            height: 80px;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 10px;
            resize: none;
            margin-bottom: 10px;
        }

        .say-btn {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .say-btn .btn {
            padding: 8px 20px;
            border-radius: 5px;
            border: none;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .say-btn .btn-default {
            background: #4facfe;
            color: #fff;
        }

        .say-btn .btn-default:hover {
            background: #00f2fe;
        }

        /* User List */
        #userlist {
            background: #f9fafc;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            height: 300px;
            overflow-y: auto;
        }

        #userlist h4 {
            font-size: 16px;
            margin-bottom: 10px;
        }

        #userlist ul {
            list-style: none;
            padding: 0;
        }

        #userlist li {
            margin-bottom: 5px;
            padding: 5px;
            border-radius: 3px;
            cursor: pointer;
            transition: background 0.3s;
        }

        #userlist li:hover {
            background: #e6f7ff;
        }

        /* Dropdown */
        #client_list {
            width: 100%;
            margin-bottom: 10px;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        /* Room Links */
        .room-links a {
            display: inline-block;
            margin-right: 10px;
            color: #4facfe;
            text-decoration: none;
            transition: color 0.3s;
        }

        .room-links a:hover {
            color: #00f2fe;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            .say-btn {
                flex-direction: column;
            }
            .say-btn .btn {
                margin-bottom: 10px;
                width: 100%;
            }
            #userlist {
                height: 200px;
            }
        }
    </style>

  <script type="text/javascript">
    if (typeof console == "undefined") {    this.console = { log: function (msg) {  } };}

    WEB_SOCKET_SWF_LOCATION = "/swf/WebSocketMain.swf";
    
    WEB_SOCKET_DEBUG = true;
    var ws, name, client_list={},room_id,client_id;

    room_id = getQueryString('room_id')?getQueryString('room_id'):1;

    function getQueryString(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) return unescape(r[2]); return null;
    } 

    function connect() {
     
       ws = new WebSocket("ws://"+document.domain+":7272");
       
       ws.onopen = onopen;
       
       ws.onmessage = onmessage; 
       ws.onclose = function() {
    	  console.log("Kết nối đã đóng, đang thử kết nối lại...");
          connect();
       };
       ws.onerror = function() {
     	  console.log("Đã xảy ra lỗi kết nối!");
       };
    }

  
    function onopen()
    {
        if(!name)
        {
            show_prompt();
        }
        
        var login_data = '{"type":"login","client_name":"'+name.replace(/"/g, '\\"')+'","room_id":'+room_id+'}';
        console.log("Kết nối WebSocket thành công, gửi dữ liệu đăng nhập: "+login_data);
        ws.send(login_data);
    }

    
    function onmessage(e)
    {
        console.log(e.data);
        var data = JSON.parse(e.data);
        switch(data['type']){
     
            case 'ping':
                ws.send('{"type":"pong"}');
                break;;
            
            case 'login':
                var client_name = data['client_name'];
                if(data['client_list'])
                {
                    client_id = data['client_id'];
                    client_name = 'Bạn';
                    client_list = data['client_list'];
                }
                else
                {
                    client_list[data['client_id']] = data['client_name']; 
                }

                say(data['client_id'], data['client_name'],  client_name+' đã tham gia phòng chat', data['time']);

                flush_client_list();
                console.log(data['client_name']+" đã đăng nhập thành công");
                break;
         
            case 'say':
                //{"type":"say","from_client_id":xxx,"to_client_id":"all/client_id","content":"xxx","time":"xxx"}
                say(data['from_client_id'], data['from_client_name'], data['content'], data['time']);
                break;
         
            case 'logout':
                //{"type":"logout","client_id":xxx,"time":"xxx"}
                say(data['from_client_id'], data['from_client_name'], data['from_client_name']+' đã rời phòng', data['time']);
                delete client_list[data['from_client_id']];
                flush_client_list();
        }
    }

 
    function show_prompt(){  
        name = prompt('Vui lòng nhập tên của bạn:', '');
        if(!name || name=='null'){  
            name = 'Khách';
        }
    }  

  
    function onSubmit() {
      var input = document.getElementById("textarea");
      var to_client_id = $("#client_list option:selected").attr("value");
      var to_client_name = $("#client_list option:selected").text();
      ws.send('{"type":"say","to_client_id":"'+to_client_id+'","to_client_name":"'+to_client_name+'","content":"'+input.value.replace(/"/g, '\\"').replace(/\n/g,'\\n').replace(/\r/g, '\\r')+'"}');
      input.value = "";
      input.focus();
    }


    function flush_client_list(){
    	var userlist_window = $("#userlist");
    	var client_list_slelect = $("#client_list");
    	userlist_window.empty();
    	client_list_slelect.empty();
    	userlist_window.append('<h4>Người dùng trực tuyến</h4><ul>');
    	client_list_slelect.append('<option value="all" id="cli_all">Tất cả</option>');
    	for(var p in client_list){
            userlist_window.append('<li id="'+p+'">'+client_list[p]+'</li>');
            if (p!=client_id) {
                client_list_slelect.append('<option value="'+p+'">'+client_list[p]+'</option>');   
            }
        }
    	$("#client_list").val(select_client_id);
    	userlist_window.append('</ul>');
    }

   
    function say(from_client_id, from_client_name, content, time){
       
        content = content.replace(/(http|https):\/\/[\w]+.sinaimg.cn[\S]+(jpg|png|gif)/gi, function(img){
            return "<a target='_blank' href='"+img+"'>"+"<img src='"+img+"'>"+"</a>";}
        );

     
        content = content.replace(/(http|https):\/\/[\S]+/gi, function(url){
            if(url.indexOf(".sinaimg.cn/") < 0)
                return "<a target='_blank' href='"+url+"'>"+url+"</a>";
            else
                return url;
        }
        );

    	$("#dialog").append('<div class="speech_item"><img src="http://lorempixel.com/38/38/?'+from_client_id+'" class="user_icon" /> '+from_client_name+' <br> '+time+'<div style="clear:both;"></div><p class="triangle-isosceles top">'+content+'</p> </div>').parseEmotion();
    }

    $(function(){
    	select_client_id = 'all';
	    $("#client_list").change(function(){
	         select_client_id = $("#client_list option:selected").attr("value");
	    });
        $('.face').click(function(event){
            $(this).sinaEmotion();
            event.stopPropagation();
        });
    });


  </script>
</head>
<body onload="connect();">
    <div class="container">
	    <div class="row clearfix">
	        <div class="col-md-1 column">
	        </div>
	        <div class="col-md-6 column">
	           <div class="thumbnail">
	               <div class="caption" id="dialog"></div>
	           </div>
	           <form onsubmit="onSubmit(); return false;">
	                <select style="margin-bottom:8px" id="client_list">
                        <option value="all">Tất cả</option>
                    </select>
                    <textarea class="textarea thumbnail" id="textarea"></textarea>
                    <div class="say-btn">
                        <input type="button" class="btn btn-default face pull-left" value="Biểu cảm" />
                        <input type="submit" class="btn btn-default" value="Gửi" />
                    </div>
               </form>
               <div>
               &nbsp;&nbsp;&nbsp;&nbsp;<b style="color:rgb(221, 228, 232);">Danh sách phòng:</b><span style="color:rgb(221, 228, 232);"> Hiện đang ở&nbsp;phòng <script>document.write(room_id)</script></span>）<br>
               &nbsp;&nbsp;&nbsp;&nbsp;<a href="/?room_id=1">Phòng 1</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="/?room_id=2">Phòng 2</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="/?room_id=3">Phòng 3</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="/?room_id=4">Phòng 4</a>
               <br><br>
               </div>
               
	        </div>
	        <div class="col-md-3 column">
	           <div class="thumbnail">
                   <div class="caption" id="userlist"></div>
               </div>
               <a href="#" target="_blank"><img style="width:252px;margin-left:5px;" src="/img/anh.jpg"></a>
	        </div>
	    </div>
    </div>
    <script type="text/javascript">var _bdhmProtocol = (("https:" == document.location.protocol) ? " https://" : " http://");document.write(unescape("%3Cscript src='" + _bdhmProtocol + "hm.baidu.com/h.js%3F7b1919221e89d2aa5711e4deb935debd' type='text/javascript'%3E%3C/script%3E"));</script>
    <script type="text/javascript">
  
      document.write('<meta name="viewport" content="width=device-width,initial-scale=1">');
      $("textarea").on("keydown", function(e) {
        
          if(e.keyCode === 13 && !e.ctrlKey) {
              e.preventDefault();
              $('form').submit();
              return false;
          }

         
          if(e.keyCode === 13 && e.ctrlKey) {
              $(this).val(function(i,val){
                  return val + "\n";
              });
          }
      });
    </script>
</body>
</html>
