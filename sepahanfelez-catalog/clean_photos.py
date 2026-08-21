# -*- coding: utf-8 -*-
"""Remove the red Toloe-Sepahan card from product photos and grade them consistently."""
import cv2, numpy as np, os, sys

SRC = "/home/mlops/mohammad/sepahanfelez-prototype-site/عکس محصولات"
OUT = sys.argv[1]
os.makedirs(OUT, exist_ok=True)
only = sys.argv[2:] or None


def card_mask(bgr):
    hsv = cv2.cvtColor(bgr, cv2.COLOR_BGR2HSV)
    h, s, v = cv2.split(hsv)
    # bright saturated red (two hue ends)
    red = (((h < 12) | (h > 168)) & (s > 90) & (v > 60)).astype(np.uint8) * 255
    red = cv2.morphologyEx(red, cv2.MORPH_CLOSE, np.ones((25, 25), np.uint8))
    n, lab_cc, stats, _ = cv2.connectedComponentsWithStats(red)
    H, W = red.shape
    seed = np.zeros_like(red)
    total_red = 0
    for i in range(1, n):
        x, y, w, hh, area = stats[i]
        if area > 0.0008 * H * W:
            seed[lab_cc == i] = 255
            total_red += area
    if total_red == 0:
        return None
    # the card also has a non-red photo strip + white circles: grab every
    # high-chroma pixel near the red blob and merge it in
    lab = cv2.cvtColor(bgr, cv2.COLOR_BGR2LAB).astype(np.int16)
    chroma = np.sqrt((lab[:, :, 1] - 128) ** 2 + (lab[:, :, 2] - 128) ** 2)
    colored = (chroma > 20).astype(np.uint8) * 255
    reach = int(max(25, 0.9 * np.sqrt(total_red)))
    near = cv2.dilate(seed, np.ones((reach, reach), np.uint8))
    mask = cv2.bitwise_or(seed, cv2.bitwise_and(colored, near))
    mask = cv2.morphologyEx(mask, cv2.MORPH_CLOSE, np.ones((31, 31), np.uint8))
    # keep only components touching the red seed (drop far-away colored floor)
    n2, lab2 = cv2.connectedComponents(mask)
    keep = np.zeros_like(mask)
    seed_ids = set(np.unique(lab2[seed > 0])) - {0}
    for i in seed_ids:
        keep[lab2 == i] = 255
    cnts, _ = cv2.findContours(keep, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    hullmask = np.zeros_like(keep)
    for c in cnts:
        cv2.fillConvexPoly(hullmask, cv2.convexHull(c), 255)
    hullmask = cv2.dilate(hullmask, np.ones((23, 23), np.uint8))
    return hullmask


def grade(bgr):
    """Consistent look: gray-world white balance + gentle contrast + slight cool tone."""
    img = bgr.astype(np.float32)
    means = img.reshape(-1, 3).mean(0)
    gray = means.mean()
    img *= gray / np.maximum(means, 1)
    # normalize exposure to a common midtone
    cur = img.mean()
    img *= np.clip(158.0 / max(cur, 1), 0.75, 1.35)
    img = np.clip(img, 0, 255).astype(np.uint8)
    lab = cv2.cvtColor(img, cv2.COLOR_BGR2LAB)
    l, a, b = cv2.split(lab)
    clahe = cv2.createCLAHE(clipLimit=1.6, tileGridSize=(8, 8))
    l = clahe.apply(l)
    lab = cv2.merge([l, a, b])
    img = cv2.cvtColor(lab, cv2.COLOR_LAB2BGR)
    return img


files = sorted(f for f in os.listdir(SRC) if f.lower().endswith(".jpg"))
if only:
    files = [f for f in files if f in only]
report = []
for f in files:
    bgr = cv2.imread(os.path.join(SRC, f))
    m = card_mask(bgr)
    tag = "none"
    if m is not None:
        frac = m.mean() / 255
        try:
            res = np.zeros_like(bgr)
            st = cv2.xphoto.inpaint(cv2.cvtColor(bgr, cv2.COLOR_BGR2Lab), (m == 0).astype(np.uint8), res, cv2.xphoto.INPAINT_SHIFTMAP)
            bgr = cv2.cvtColor(res, cv2.COLOR_Lab2BGR)
            tag = f"shiftmap {frac:.1%}"
        except Exception as e:
            bgr = cv2.inpaint(bgr, m, 9, cv2.INPAINT_TELEA)
            tag = f"telea {frac:.1%} ({e})"
    out = grade(bgr)
    # normalize size: max side 1100
    h, w = out.shape[:2]
    sc = 1100.0 / max(h, w)
    if sc < 1:
        out = cv2.resize(out, (int(w * sc), int(h * sc)), interpolation=cv2.INTER_AREA)
    cv2.imwrite(os.path.join(OUT, f), out, [cv2.IMWRITE_JPEG_QUALITY, 88])
    report.append(f"{f}: {tag}")
print("\n".join(report))
print("done", len(files))
