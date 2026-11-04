"""Bereidt alle beelden voor de Hydynamic-opbouw voor in scratchpad/media + manifest.json."""
import json, os, sys, glob, re, urllib.request
from PIL import Image

BASE = os.path.dirname(os.path.abspath(__file__))
THEME = r'c:/laragon/www/Hydynamic/wp-content/themes/Mediakanjers'
OUT = os.path.join(BASE, 'media')
os.makedirs(OUT, exist_ok=True)
REF = THEME + '/design/referentie/'
manifest = []

def add(key, file, alt, title=None):
    manifest.append({'key': key, 'file': os.path.join(OUT, file), 'alt': alt, 'title': title or alt})

def refine(im, box, pad=14, thr=28):
    """Zoek de exacte rand van een foto binnen een ruwe box (achtergrond = randkleur)."""
    x0, y0, x1, y1 = box
    region = im.crop((x0 - pad, y0 - pad, x1 + pad, y1 + pad)).convert('RGB')
    bg = region.getpixel((2, 2))
    w, h = region.size
    px = region.load()
    def diff(p): return sum(abs(p[i] - bg[i]) for i in range(3))
    cols = [x for x in range(w) if sum(1 for y in range(0, h, 3) if diff(px[x, y]) > thr) > h / 9]
    rows = [y for y in range(h) if sum(1 for x in range(0, w, 3) if diff(px[x, y]) > thr) > w / 9]
    if not cols or not rows:
        return box
    return (x0 - pad + cols[0], y0 - pad + rows[0], x0 - pad + cols[-1] + 1, y0 - pad + rows[-1] + 1)

def crop(src, box, file, alt, key, do_refine=True, inset=3):
    im = Image.open(REF + src).convert('RGB')
    b = refine(im, box) if do_refine else box
    # afgeronde hoeken wegsnijden
    b = (b[0] + inset, b[1] + inset, b[2] - inset, b[3] - inset)
    im.crop(b).save(os.path.join(OUT, file), quality=90, optimize=True)
    add(key, file, alt)

H = 'categorie-hydrauliek-slangen-en-koppelingen.png'
C = 'categorie-centrale-smering.png'
I = 'categorie-industrieel.png'
P = 'product-polyamide-buizen-pa12.png'
O = 'oplossing-offshore-en-maritiem.png'

tile_x = [(151, 527), (565, 941), (979, 1355), (1393, 1769)]
tiles = {
    H: [('tile-hydrauliek-slangen', 'Hydrauliekslang met persfittingen'), ('tile-fittingen-adapters', 'Hydraulische fittingen en adapters'),
        ('tile-perskoppelingen', 'Perskoppelingen voor hydrauliekslangen'), ('tile-flexmaster', 'Danfoss Flexmaster koppelingen')],
    C: [('tile-polyamide-leidingen', 'Polyamide leiding PA12 op rol'), ('tile-push-in', 'Push-in connectoren'),
        ('tile-smeernippels', 'Smeernippels en vetspuit'), ('tile-nippelblokken', 'Nippelblok met accessoires')],
    I: [('tile-zuivelkoppelingen', 'Zuivelkoppelingen conform DIN 11851'), ('tile-storz', 'Storz koppelingen'),
        ('tile-camlock', 'Camlock koppelingen'), ('tile-tankwagen', 'Tankwagenkoppelingen')],
}
for src, items in tiles.items():
    for (x0, x1), (key, alt) in zip(tile_x, items):
        crop(src, (x0, 371, x1, 640), key + '.jpg', alt, key, do_refine=False, inset=2)

crop(H, (979, 1596, 1768, 2102), 'foto-slangen-fittingen.jpg', 'Hydraulische slangen met fittingen en perskoppelingen op een machine', 'foto-slangen-fittingen')
crop(H, (151, 2282, 940, 2788), 'foto-assemblage-dnv.jpg', 'Medewerker controleert een gegraveerde, DNV-gecertificeerde slangassemblage', 'foto-assemblage-dnv')
crop(C, (978, 1526, 1768, 1970), 'foto-ontwikkeling-productie.jpg', 'Gefreesde staalcomponenten met technische tekening en schuifmaat', 'foto-ontwikkeling-productie')
crop(C, (150, 2118, 940, 2556), 'foto-direct-startklaar.jpg', 'Met vet gevulde leidingen aangesloten op een verdeelblok', 'foto-direct-startklaar')
crop(P, (978, 394, 1768, 836), 'foto-pa12-buizen.jpg', 'Zwarte polyamide buizen (PA12) op rol', 'foto-pa12-buizen')
crop(P, (978, 1766, 1768, 2220), 'foto-werkdruk.jpg', 'PA12-leiding aangesloten op een smeerpomp', 'foto-werkdruk')
crop(O, (978, 198, 1768, 788), 'foto-offshore-hero.jpg', 'Polyamide leidingen voor hydraulische toepassingen offshore en maritiem', 'foto-offshore-hero')

# Danfoss-logo (rood vlak + "Engineering Tomorrow") — ruim uitsnijden op de grijze achtergrond
im = Image.open(REF + H).convert('RGB')
im.crop((1110, 2982, 1548, 3104)).save(os.path.join(OUT, 'logo-danfoss.png'), optimize=True)
add('logo-danfoss', 'logo-danfoss.png', 'Danfoss – Engineering Tomorrow')

# Sfeerfoto's (12 HR-foto's) → 1600px breed
sfeer = sorted(glob.glob(THEME + '/sfeer/*.jpg'))
for i, f in enumerate(sfeer):
    img = Image.open(f)
    img = img.convert('RGB')
    img.thumbnail((1600, 1600))
    name = 'sfeer-hydynamic-%02d.jpg' % (i + 1)
    img.save(os.path.join(OUT, name), quality=82, optimize=True, progressive=True)
    add('sfeer-%02d' % (i + 1), name, 'Sfeerimpressie Hydynamic Enschede %d' % (i + 1))

# Over ons / contact (uit de Claude-ontwerpen)
for f, key, alt in [
    ('foto-bedrijfspand.jpg', 'foto-bedrijfspand', 'Bedrijfspand van Hydynamic Holland BV aan de Marssteden 28 in Enschede'),
    ('foto-team.jpg', 'foto-team', 'Het team van Hydynamic voor het bedrijfspand in Enschede, met slangen en koppelingen in de hand'),
    ('logo-abs.png', 'logo-abs', 'ABS (American Bureau of Shipping)'),
    ('logo-bureau-veritas.png', 'logo-bureau-veritas', 'Bureau Veritas'),
    ('logo-lloyds-register.png', 'logo-lloyds-register', 'Lloyd’s Register'),
    ('dnv-iso-9001.svg', 'logo-dnv', 'DNV managementsysteemcertificaat ISO 9001'),
    ('logo-hydynamic-wit.svg', 'logo-hydynamic', 'Hydynamic'),
]:
    data = open(THEME + '/design/assets/' + f, 'rb').read()
    open(os.path.join(OUT, f), 'wb').write(data)
    add(key, f, alt)

# Live site: homepage-beelden en iconen
UA = {'User-Agent': 'Mozilla/5.0 (compatible; hydynamic-rebuild)'}
def fetch(url, file):
    path = os.path.join(OUT, file)
    if not os.path.exists(path):
        req = urllib.request.Request(url, headers=UA)
        open(path, 'wb').write(urllib.request.urlopen(req, timeout=60).read())
    return path

live = [
    ('https://www.hydynamic.nl/wp-content/uploads/2024/06/Achtergrond-website.jpg', 'home-achtergrond.jpg', 'home-hero', 'Hydraulische slangen en koppelingen op een machine'),
    ('https://www.hydynamic.nl/wp-content/uploads/2025/07/Bedrijfspand-HH-1920x1440.jpg', 'home-bedrijfspand-drone.jpg', 'home-pand', 'Luchtfoto van het bedrijfspand van Hydynamic in Enschede'),
    ('https://www.hydynamic.nl/wp-content/uploads/2025/06/Centrale-smering.svg', 'icoon-centrale-smering.svg', 'icon-smering', 'Icoon centrale smering'),
    ('https://www.hydynamic.nl/wp-content/uploads/2025/06/Klantspecifiek.svg', 'icoon-klantspecifiek.svg', 'icon-klantspecifiek', 'Icoon klantspecifiek'),
    ('https://www.hydynamic.nl/wp-content/uploads/2025/06/Hydrauliek.svg', 'icoon-hydrauliek.svg', 'icon-hydrauliek', 'Icoon hydrauliek'),
    ('https://www.hydynamic.nl/wp-content/uploads/2025/06/Industrial.svg', 'icoon-industrieel.svg', 'icon-industrieel', 'Icoon industrieel'),
]
for url, file, key, alt in live:
    fetch(url, file)
    if file.endswith('.jpg'):
        img = Image.open(os.path.join(OUT, file)).convert('RGB')
        img.thumbnail((2400, 2400))
        img.save(os.path.join(OUT, file), quality=82, optimize=True, progressive=True)
    add(key, file, alt)

# Kennisbank: uitgelichte afbeeldingen + afbeeldingen in de tekst
posts = json.load(open(os.path.join(BASE, 'live', 'posts.json'), encoding='utf-8'))
for p in posts:
    fm = p.get('_embedded', {}).get('wp:featuredmedia', [{}])[0]
    if fm.get('source_url'):
        ext = os.path.splitext(fm['source_url'])[1].lower()
        file = 'kb-%d%s' % (p['id'], ext)
        fetch(fm['source_url'], file)
        img = Image.open(os.path.join(OUT, file))
        if max(img.size) > 1600:
            img.thumbnail((1600, 1600))
            img.save(os.path.join(OUT, file), optimize=True, **({'quality': 82} if ext in ('.jpg', '.jpeg') else {}))
        add('kb-%d' % p['id'], file, fm.get('alt_text') or re.sub('<[^>]+>', '', p['title']['rendered']))
    for i, src in enumerate(re.findall(r'<img[^>]+src="([^"]+)"', p['content']['rendered'])):
        ext = os.path.splitext(src.split('?')[0])[1].lower() or '.jpg'
        file = 'kb-%d-inline-%d%s' % (p['id'], i, ext)
        try:
            fetch(src.replace('www.hydynamic.com', 'www.hydynamic.nl'), file)
            add('kbi-%d-%d' % (p['id'], i), file, '')
            manifest[-1]['source'] = src
        except Exception as e:
            print('inline image failed', src, e)

json.dump(manifest, open(os.path.join(OUT, 'manifest.json'), 'w', encoding='utf-8'), indent=1, ensure_ascii=False)
print(len(manifest), 'bestanden klaar')
