import shutil

src = r"C:\Users\Lê Nhựt Khánh\.gemini\antigravity-ide\brain\37b05d48-c3f6-4958-8e6a-b8769fd4a83b\lofi_boy_cat_rooftop_1788337295059.jpg"
dest1 = r"c:\xampp\htdocs\tkb\assets\img\lofi_night_sky.jpg"
dest2 = r"c:\xampp\htdocs\tkb\assets\img\lofi_boy_cat.jpg"

shutil.copyfile(src, dest1)
shutil.copyfile(src, dest2)
print("SUCCESS: Copied new lofi boy + cat rooftop banner!")
