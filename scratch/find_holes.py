import sys
sys.stdout.reconfigure(encoding='utf-8')
from PIL import Image
import collections

def find_enclosed_holes(png_path):
    img = Image.open(png_path).convert("RGBA")
    width, height = img.size
    pixels = img.load()
    
    # Any pixel with alpha > 0 that is pure studio white (r > 240, g > 240, b > 240)
    # Check connected components of these pixels
    visited = set()
    holes = []
    
    for y in range(height):
        for x in range(width):
            if (x, y) not in visited:
                r, g, b, a = pixels[x, y]
                if a > 100:
                    bright = (r + g + b) / 3.0
                    diff = max(abs(r-g), abs(g-b), abs(r-nb if 'nb' in locals() else abs(r-b)))
                    if bright > 242 and diff < 10:
                        # BFS component
                        comp = []
                        q = collections.deque([(x, y)])
                        visited.add((x, y))
                        while q:
                            cx, cy = q.popleft()
                            comp.append((cx, cy))
                            for dx, dy in [(-1,0), (1,0), (0,-1), (0,1)]:
                                nx, ny = cx + dx, cy + dy
                                if 0 <= nx < width and 0 <= ny < height and (nx, ny) not in visited:
                                    nr, ng, nb, na = pixels[nx, ny]
                                    if na > 100 and (nr+ng+nb)/3.0 > 240 and max(abs(nr-ng), abs(ng-nb), abs(nr-nb)) < 12:
                                        visited.add((nx, ny))
                                        q.append((nx, ny))
                        if len(comp) > 30:
                            # calculate bounding box
                            min_x = min(p[0] for p in comp)
                            max_x = max(p[0] for p in comp)
                            min_y = min(p[1] for p in comp)
                            max_y = max(p[1] for p in comp)
                            holes.append((len(comp), min_x, min_y, max_x, max_y))
                            
    print("Found bright white components:", holes)

find_enclosed_holes(r"c:\xampp\htdocs\tkb\assets\ai\vutru_hikari_5d_idle.png")
