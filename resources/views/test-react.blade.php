{{-- resources/views/test-react.blade.php --}}
    <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تست React</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-8">
    <!-- اضافه کردن یک متن برای تست -->
    <h1 class="text-2xl mb-4">صفحه تست React</h1>

    <!-- اضافه کردن div برای دیباگ -->
    <div class="mb-4 p-4 bg-yellow-100">
        این متن باید دیده شود
    </div>

    <!-- div اصلی برای React -->
    <div id="react-test"></div>
</div>

<!-- اضافه کردن اسکریپت برای دیباگ -->
<script>
    console.log('صفحه لود شده است');
    console.log('Checking react-test element:', document.getElementById('react-test'));
</script>
</body>
</html>
