import asyncio
import edge_tts
import time

VOICES = [
    ("vi-VN-HoaiMyNeural", "+0Hz", "+0%", "Xin chào, tôi là Thuỳ Tiên phát thanh viên miền Bắc."),
    ("vi-VN-NamMinhNeural", "-10Hz", "-5%", "Chào bạn, tôi là Tuấn Hùng giọng nam miền Bắc trầm ấm."),
    ("vi-VN-HoaiMyNeural", "+30Hz", "+15%", "Dạ em chào anh chị, em là Mai Phương giọng miền Nam ngọt ngào đây nè!"),
    ("en-US-JennyNeural", "+0Hz", "+0%", "Hello! I am Sarah from the United States."),
    ("en-GB-RyanNeural", "-10Hz", "-5%", "This is David, presenting the latest technology broadcast."),
    ("ja-JP-NanamiNeural", "+25Hz", "+10%", "Konnichiwa! Ogenki desu ka?")
]

async def test_all():
    for voice, pitch, rate, text in VOICES:
        t0 = time.time()
        tts = edge_tts.Communicate(text, voice, pitch=pitch, rate=rate)
        out_file = f"c:/xampp/htdocs/tkb/scratch/out_{voice}_{pitch}.mp3".replace("+", "p").replace("-", "m").replace("%", "")
        await tts.save(out_file)
        print(f"Done {voice} ({pitch}, {rate}) in {time.time()-t0:.2f}s -> {out_file}")

if __name__ == "__main__":
    asyncio.run(test_all())
