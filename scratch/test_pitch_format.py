import asyncio
import edge_tts

async def test():
    # Test percentage pitch vs Hz pitch on NamMinh
    for p in ["+0%", "+5%", "-5%", "+10%", "-10%", "+0Hz", "+5Hz", "-5Hz"]:
        try:
            tts = edge_tts.Communicate("Xin chào đây là Tuấn Hùng", "vi-VN-NamMinhNeural", pitch=p, rate="+0%")
            await tts.save(f"c:/xampp/htdocs/tkb/scratch/test_nm_{p}.mp3".replace("+", "p").replace("-", "m").replace("%", "pct"))
            print(f"Pitch {p} -> SUCCESS")
        except Exception as e:
            print(f"Pitch {p} -> FAILED: {e}")

if __name__ == "__main__":
    asyncio.run(test())
