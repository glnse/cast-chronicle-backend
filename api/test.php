<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script>
    function testRequest(){
        $.ajax({
            type: 'GET',
            url: 'post.php',
            data: {
                id:'1',post:'2'
            },
            success: function(r){
                console.log(r);
            }
        });
    }
    </script>
</body>
</html>