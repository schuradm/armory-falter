# Kontaktbogen: python3 sheet.py out_dir sheet.png [cols]
import sys, glob, os
from PIL import Image, ImageDraw
d, outp = sys.argv[1], sys.argv[2]; cols = int(sys.argv[3]) if len(sys.argv) > 3 else 3
files = sorted(glob.glob(os.path.join(d, '*.png')))
tw, th = 640, 480
rows = (len(files) + cols - 1) // cols
sheet = Image.new('RGB', (cols * tw, rows * (th + 24)), '#000')
dr = ImageDraw.Draw(sheet)
for i, f in enumerate(files):
    im = Image.open(f).convert('RGB').resize((tw, th), Image.LANCZOS)
    x, y = (i % cols) * tw, (i // cols) * (th + 24)
    sheet.paste(im, (x, y)); dr.text((x + 6, y + th + 4), os.path.basename(f)[:-4], fill='#ccc')
sheet.save(outp)
