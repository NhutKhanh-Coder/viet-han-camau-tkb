import sys
import os
import argparse
import asyncio
import json
import edge_tts

# Voice configuration mapping to actual neural models & distinctive configurations
VOICE_MAP = {
    'vi_thuytien': {'neural_voice': 'vi-VN-HoaiMyNeural', 'base_pitch': 0, 'base_rate': 0},
    'vi_maiphuong': {'neural_voice': 'vi-VN-HoaiMyNeural', 'base_pitch': 15, 'base_rate': 6},
    'vi_tuanhung': {'neural_voice': 'vi-VN-NamMinhNeural', 'base_pitch': 0, 'base_rate': 0},
    'vi_quangdung': {'neural_voice': 'vi-VN-NamMinhNeural', 'base_pitch': -10, 'base_rate': -5},
    'vi_chihang': {'neural_voice': 'vi-VN-HoaiMyNeural', 'base_pitch': 10, 'base_rate': -8},
    'vi_thaygiao': {'neural_voice': 'vi-VN-NamMinhNeural', 'base_pitch': -5, 'base_rate': -3},
    'vi_mcthoisu': {'neural_voice': 'vi-VN-HoaiMyNeural', 'base_pitch': 5, 'base_rate': 25},
    'vi_bacbaphi': {'neural_voice': 'vi-VN-NamMinhNeural', 'base_pitch': -5, 'base_rate': 12},
    'vi_cotam': {'neural_voice': 'vi-VN-HoaiMyNeural', 'base_pitch': 20, 'base_rate': -4},
    'en_sarah': {'neural_voice': 'en-US-JennyNeural', 'base_pitch': 0, 'base_rate': 0},
    'en_alex': {'neural_voice': 'en-US-GuyNeural', 'base_pitch': 0, 'base_rate': 0},
    'en_emma': {'neural_voice': 'en-GB-SoniaNeural', 'base_pitch': 0, 'base_rate': 0},
    'en_david': {'neural_voice': 'en-GB-RyanNeural', 'base_pitch': -10, 'base_rate': -5},
    'en_narrator': {'neural_voice': 'en-US-ChristopherNeural', 'base_pitch': -15, 'base_rate': -10},
    'char_bot': {'neural_voice': 'en-US-GuyNeural', 'base_pitch': -20, 'base_rate': 15},
    'char_fairy': {'neural_voice': 'vi-VN-HoaiMyNeural', 'base_pitch': 25, 'base_rate': -10},
    'char_wizard': {'neural_voice': 'vi-VN-NamMinhNeural', 'base_pitch': -20, 'base_rate': -10},
    'char_cartoon': {'neural_voice': 'vi-VN-HoaiMyNeural', 'base_pitch': 30, 'base_rate': 15},
    'char_monster': {'neural_voice': 'vi-VN-NamMinhNeural', 'base_pitch': -25, 'base_rate': -10},
    'meme_google': {'neural_voice': 'vi-VN-HoaiMyNeural', 'base_pitch': 0, 'base_rate': 15},
    'meme_rick': {'neural_voice': 'en-US-GuyNeural', 'base_pitch': -5, 'base_rate': 8},
    'meme_pepe': {'neural_voice': 'en-US-ChristopherNeural', 'base_pitch': -15, 'base_rate': -5},
    'meme_anime': {'neural_voice': 'ja-JP-NanamiNeural', 'base_pitch': 20, 'base_rate': 10}
}

async def generate_speech(text, voice_id, slider_pitch=1.0, slider_rate=1.0, output_path=None):
    if not text:
        raise ValueError("Text is empty")

    cfg = VOICE_MAP.get(voice_id, None)
    if not cfg:
        if voice_id and 'Neural' in voice_id:
            neural_voice = voice_id
            base_pitch = 0
            base_rate = 0
        else:
            neural_voice = 'vi-VN-HoaiMyNeural'
            base_pitch = 0
            base_rate = 0
    else:
        neural_voice = cfg['neural_voice']
        base_pitch = cfg['base_pitch']
        base_rate = cfg['base_rate']

    # Pitch MUST be formatted in Hz (e.g. '+0Hz', '+10Hz', '-5Hz')
    pitch_val = int(base_pitch + (slider_pitch - 1.0) * 30)
    pitch_val = max(-50, min(50, pitch_val))
    pitch_str = f"{'+' if pitch_val >= 0 else ''}{pitch_val}Hz"

    # Rate MUST be formatted in % (e.g. '+0%', '+10%', '-5%')
    rate_val = int(base_rate + (slider_rate - 1.0) * 50)
    rate_val = max(-50, min(100, rate_val))
    rate_str = f"{'+' if rate_val >= 0 else ''}{rate_val}%"

    tts = edge_tts.Communicate(text, neural_voice, pitch=pitch_str, rate=rate_str)
    
    if output_path:
        os.makedirs(os.path.dirname(os.path.abspath(output_path)), exist_ok=True)
        await tts.save(output_path)
        return output_path
    else:
        chunks = []
        async for chunk in tts.stream():
            if chunk["type"] == "audio":
                chunks.append(chunk["data"])
        return b"".join(chunks)

def main():
    parser = argparse.ArgumentParser(description="TTS Engine")
    parser.add_argument("--text", type=str, required=True, help="Text to speak")
    parser.add_argument("--voice", type=str, default="vi_thuytien", help="Voice ID")
    parser.add_argument("--pitch", type=float, default=1.0, help="Pitch (0.5 - 1.5)")
    parser.add_argument("--rate", type=float, default=1.0, help="Rate (0.5 - 2.0)")
    parser.add_argument("--output", type=str, required=True, help="Output MP3 path")

    args = parser.parse_args()

    try:
        asyncio.run(generate_speech(args.text, args.voice, args.pitch, args.rate, args.output))
        print(json.dumps({"success": True, "output": args.output, "voice": args.voice}))
    except Exception as e:
        print(json.dumps({"success": False, "error": str(e)}), file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    main()
