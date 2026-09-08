<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Test Folder</title></head>
<body style="font-family:Arial;padding:40px;text-align:center;">
<h2>Test chọn Folder trên trình duyệt</h2>

<button onclick="test1()" style="padding:15px 30px;font-size:18px;background:#10b981;color:#fff;border:none;border-radius:10px;cursor:pointer;margin:10px;">
Test 1: showDirectoryPicker (API)
</button>
<br><br>
<button onclick="test2()" style="padding:15px 30px;font-size:18px;background:#3b82f6;color:#fff;border:none;border-radius:10px;cursor:pointer;margin:10px;">
Test 2: input webkitdirectory (JS)
</button>
<br><br>
<div id="result" style="margin-top:20px;padding:20px;background:#f0f0f0;border-radius:10px;text-align:left;max-width:600px;margin-left:auto;margin-right:auto;"></div>

<script>
async function test1() {
    document.getElementById('result').innerHTML = '<b>Đang mở showDirectoryPicker...</b>';
    if (!window.showDirectoryPicker) {
        document.getElementById('result').innerHTML = '<b style="color:red;">showDirectoryPicker KHÔNG khả dụng trên trình duyệt này!</b><br>Trình duyệt: ' + navigator.userAgent;
        return;
    }
    try {
        var d = await window.showDirectoryPicker({ mode: 'read' });
        document.getElementById('result').innerHTML = '<b style="color:green;">THÀNH CÔNG! Đã chọn folder: ' + d.name + '</b>';
    } catch(e) {
        document.getElementById('result').innerHTML = '<b style="color:red;">LỖI: ' + e.name + ' - ' + e.message + '</b><br>Trình duyệt: ' + navigator.userAgent;
    }
}

function test2() {
    document.getElementById('result').innerHTML = '<b>Đang mở input webkitdirectory...</b>';
    var inp = document.createElement('input');
    inp.type = 'file';
    inp.multiple = true;
    inp.webkitdirectory = true;
    inp.setAttribute('webkitdirectory', '');
    inp.style.display = 'none';
    document.body.appendChild(inp);
    inp.onchange = function() {
        var files = Array.from(this.files);
        var html = '<b style="color:green;">THÀNH CÔNG! Đã chọn ' + files.length + ' tệp</b><br>';
        if (files[0]) html += 'Folder: ' + files[0].webkitRelativePath.split('/')[0] + '<br>';
        files.forEach(function(f) { html += '📄 ' + f.webkitRelativePath + '<br>'; });
        document.getElementById('result').innerHTML = html;
        document.body.removeChild(inp);
    };
    inp.click();
}
</script>
</body></html>
