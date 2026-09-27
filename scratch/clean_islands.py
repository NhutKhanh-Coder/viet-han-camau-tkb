import sys
sys.stdout.reconfigure(encoding='utf-8')
from PIL import Image
import collections

def remove_small_islands(path):
    img = Image.open(path).convert("RGBA")
    w, h = img.size
    pixels = img.load()
    
    # Connected component labeling of non-transparent pixels (a > 30)
    visited = set()
    islands = []
    
    for y in range(h):
        for x in range(w):
            if (x, y) not in visited and pixels[x, y][3] > 30:
                comp = []
                q = collections.deque([(x, y)])
                visited.add((x, y))
                while q:
                    cx, cy = q.popleft()
                    comp.append((cx, cy))
                    for dx, dy in [(-1,0), (1,0), (0,-1), (0,1)]:
                        nx, ny = cx + dx, cy + dy
                        if 0 <= nx < w and 0 <= ny < h and (nx, ny) not in visited:
                            if pixels[nx, ny][3] > 30:
                                visited.add((nx, ny))
                                q.append((nx, ny))
                islands.append(comp)
                
    # Keep only large components (main body is > 100,000 pixels)
    islands.sort(key=lambda c: len(c), reverse=True)
    print("Found components:", [len(c) for c in islands[:6]])
    
    # Clear all components smaller than 500 pixels (floating lines/dots)
    cleared = 0
    for comp in islands[1:]:
        if len(comp) < 1000:
            for x, y in comp:
                pixels[x, y] = (0, 0, 0, 0)
                cleared += 1
                
    print(f"Cleared {cleared} stray island pixels for {path}")
    img.save(path, "PNG")
    webp_path = path.replace(".png", ".webp")
    img.save(webp_path, "WEBP", quality=95)

remove_small_islands(r"c:\xampp\htdocs\tkb\assets\ai\vutru_ani_grok_idle.png")
remove_small_islands(r"c:\xampp\htdocs\tkb\assets\ai\vutru_ani_grok_happy.png")
