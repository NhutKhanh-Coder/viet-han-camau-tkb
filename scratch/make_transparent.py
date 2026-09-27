import sys
sys.stdout.reconfigure(encoding='utf-8')
sys.stderr.reconfigure(encoding='utf-8')
from PIL import Image, ImageFilter

def remove_white_bg(input_path, output_png_path, output_webp_path):
    print(f"Processing: {input_path}")
    img = Image.open(input_path).convert("RGBA")
    width, height = img.size
    
    # Let's inspect corners to know background color
    corners = [
        img.getpixel((5, 5)),
        img.getpixel((width - 5, 5)),
        img.getpixel((5, height - 5)),
        img.getpixel((width - 5, height - 5))
    ]
    print(f"Corners: {corners}")

    # Flood fill from corners or use distance to white/near-white
    # Since the character has white areas (coat/jacket), a flood fill or edge-aware mask from edges is much safer than global threshold!
    # Let's create an alpha mask using flood fill from outer border:
    import collections
    
    # Convert image to grayscale for flood fill
    # Background is very bright (>240)
    visited = set()
    queue = collections.deque()
    
    # Add all border pixels that are near white
    for x in range(width):
        for y in [0, 1, 2, height - 3, height - 2, height - 1]:
            r, g, b, a = img.getpixel((x, y))
            if r > 220 and g > 220 and b > 220:
                queue.append((x, y))
                visited.add((x, y))
                
    for y in range(height):
        for x in [0, 1, 2, width - 3, width - 2, width - 1]:
            if (x, y) not in visited:
                r, g, b, a = img.getpixel((x, y))
                if r > 220 and g > 220 and b > 220:
                    queue.append((x, y))
                    visited.add((x, y))
                    
    # BFS flood fill
    while queue:
        cx, cy = queue.popleft()
        for dx, dy in [(-1, 0), (1, 0), (0, -1), (0, 1)]:
            nx, ny = cx + dx, cy + dy
            if 0 <= nx < width and 0 <= ny < height and (nx, ny) not in visited:
                r, g, b, a = img.getpixel((nx, ny))
                # Background is white/light gray/slight shadow near floor
                # If brightness is high enough or near white
                brightness = (r + g + b) / 3.0
                diff_rg = abs(r - g)
                diff_gb = abs(g - b)
                # Pure studio background is nearly neutral gray/white
                if brightness > 225 and diff_rg < 18 and diff_gb < 18:
                    visited.add((nx, ny))
                    queue.append((nx, ny))
                elif brightness > 240:
                    visited.add((nx, ny))
                    queue.append((nx, ny))

    print(f"Background pixels found: {len(visited)} of {width * height} ({len(visited)/(width*height)*100:.1f}%)")
    
    # Create mask
    mask = Image.new("L", (width, height), 255)
    mask_pixels = mask.load()
    for x, y in visited:
        mask_pixels[x, y] = 0
        
    # Soften edges slightly for anti-aliasing
    # Feather mask
    feathered = mask.filter(ImageFilter.GaussianBlur(radius=1.2))
    
    # Put alpha
    result = img.copy()
    result.putalpha(feathered)
    
    # Save transparent PNG and WebP
    result.save(output_png_path, "PNG")
    result.save(output_webp_path, "WEBP", quality=95)
    print(f"Saved: {output_png_path} and {output_webp_path}")

if __name__ == "__main__":
    base = r"C:\Users\Lê Nhựt Khánh\.gemini\antigravity-ide\brain\d4d57150-886f-4a43-bcba-500291b696d8"
    idle_in = base + r"\hikari_5d_cutout_1789830564143.jpg"
    happy_in = base + r"\hikari_5d_happy_1789830590349.jpg"
    
    out_dir = r"c:\xampp\htdocs\tkb\assets\ai"
    remove_white_bg(idle_in, out_dir + r"\vutru_hikari_5d_idle.png", out_dir + r"\vutru_hikari_5d_idle.webp")
    remove_white_bg(happy_in, out_dir + r"\vutru_hikari_5d_happy.png", out_dir + r"\vutru_hikari_5d_happy.webp")
