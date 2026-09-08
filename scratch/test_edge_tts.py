import asyncio
import edge_tts
import sys

async def test():
    try:
        print("Starting HoaiMy...")
        tts1 = edge_tts.Communicate("Xin chào", "vi-VN-HoaiMyNeural")
        await tts1.save("c:/xampp/htdocs/tkb/scratch/test_hoaimy.mp3")
        print("HoaiMy success!")
    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)

if __name__ == "__main__":
    asyncio.run(test())
