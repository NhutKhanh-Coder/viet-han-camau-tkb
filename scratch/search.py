import urllib.request, urllib.parse, re

data = urllib.parse.urlencode({'q': '"Visual Studio .Net là" "Hệ điều hành mới của Microsoft"'}).encode()
req = urllib.request.Request('https://html.duckduckgo.com/html/', data=data, headers={'User-Agent': 'Mozilla/5.0'})
try:
    with urllib.request.urlopen(req) as resp:
        html = resp.read().decode('utf-8', errors='ignore')
        for title, url in re.findall(r'<a class="result__url"[^>]*href="([^"]+)"[^>]*>(.*?)</a>', html)[:5]:
            print(f"URL: {url} | Link: {title}")
        for snippet in re.findall(r'<a class="result__snippet[^>]*>(.*?)</a>', html)[:5]:
            print(f"Snippet: {snippet}\n")
except Exception as e:
    print("Error:", e)
