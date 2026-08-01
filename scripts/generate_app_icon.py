#!/usr/bin/env python3
import math
import os
import struct
import zlib


ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))


def hex_rgb(value):
    value = value.lstrip('#')
    return tuple(int(value[i:i + 2], 16) for i in (0, 2, 4))


def mix(a, b, t):
    return tuple(int(round(a[i] + (b[i] - a[i]) * t)) for i in range(3))


def clamp(value, lower=0.0, upper=1.0):
    return max(lower, min(upper, value))


def chunk(name, data):
    payload = name + data
    return struct.pack('>I', len(data)) + payload + struct.pack('>I', zlib.crc32(payload) & 0xFFFFFFFF)


def write_png(path, width, height, pixels):
    raw = bytearray()
    stride = width * 3
    for y in range(height):
      raw.append(0)
      row_start = y * stride
      raw.extend(pixels[row_start:row_start + stride])

    with open(path, 'wb') as handle:
        handle.write(b'\x89PNG\r\n\x1a\n')
        handle.write(chunk(b'IHDR', struct.pack('>IIBBBBB', width, height, 8, 2, 0, 0, 0)))
        handle.write(chunk(b'IDAT', zlib.compress(bytes(raw), 9)))
        handle.write(chunk(b'IEND', b''))


def write_png_rgba(path, width, height, pixels):
    raw = bytearray()
    stride = width * 4
    for y in range(height):
      raw.append(0)
      row_start = y * stride
      raw.extend(pixels[row_start:row_start + stride])

    with open(path, 'wb') as handle:
        handle.write(b'\x89PNG\r\n\x1a\n')
        handle.write(chunk(b'IHDR', struct.pack('>IIBBBBB', width, height, 8, 6, 0, 0, 0)))
        handle.write(chunk(b'IDAT', zlib.compress(bytes(raw), 9)))
        handle.write(chunk(b'IEND', b''))


def put_pixel(pixels, width, height, x, y, color, alpha=1.0):
    if x < 0 or y < 0 or x >= width or y >= height or alpha <= 0:
        return
    alpha = clamp(alpha)
    i = (y * width + x) * 3
    inv = 1.0 - alpha
    pixels[i] = int(round(pixels[i] * inv + color[0] * alpha))
    pixels[i + 1] = int(round(pixels[i + 1] * inv + color[1] * alpha))
    pixels[i + 2] = int(round(pixels[i + 2] * inv + color[2] * alpha))


def put_pixel_rgba(pixels, width, height, x, y, color, alpha=1.0):
    if x < 0 or y < 0 or x >= width or y >= height or alpha <= 0:
        return
    src_a = clamp(alpha)
    i = (y * width + x) * 4
    dst_a = pixels[i + 3] / 255.0
    out_a = src_a + dst_a * (1.0 - src_a)
    if out_a <= 0:
        return
    for channel in range(3):
        dst = pixels[i + channel] / 255.0
        src = color[channel] / 255.0
        out = (src * src_a + dst * dst_a * (1.0 - src_a)) / out_a
        pixels[i + channel] = int(round(out * 255))
    pixels[i + 3] = int(round(out_a * 255))


def rounded_rect_alpha(x, y, left, top, right, bottom, radius):
    cx = (left + right) / 2.0
    cy = (top + bottom) / 2.0
    hx = (right - left) / 2.0 - radius
    hy = (bottom - top) / 2.0 - radius
    qx = abs(x - cx) - hx
    qy = abs(y - cy) - hy
    outside = math.hypot(max(qx, 0.0), max(qy, 0.0))
    inside = min(max(qx, qy), 0.0)
    distance = outside + inside - radius
    return clamp(0.5 - distance)


def draw_rounded_rect(pixels, width, height, box, radius, color, alpha=1.0):
    left, top, right, bottom = box
    for y in range(max(0, int(top - 2)), min(height, int(bottom + 3))):
        for x in range(max(0, int(left - 2)), min(width, int(right + 3))):
            edge = rounded_rect_alpha(x + 0.5, y + 0.5, left, top, right, bottom, radius)
            if edge:
                put_pixel(pixels, width, height, x, y, color, alpha * edge)


def draw_rounded_rect_rgba(pixels, width, height, box, radius, color, alpha=1.0):
    left, top, right, bottom = box
    for y in range(max(0, int(top - 2)), min(height, int(bottom + 3))):
        for x in range(max(0, int(left - 2)), min(width, int(right + 3))):
            edge = rounded_rect_alpha(x + 0.5, y + 0.5, left, top, right, bottom, radius)
            if edge:
                put_pixel_rgba(pixels, width, height, x, y, color, alpha * edge)


def draw_circle(pixels, width, height, cx, cy, radius, color, alpha=1.0):
    left = max(0, int(cx - radius - 2))
    right = min(width, int(cx + radius + 3))
    top = max(0, int(cy - radius - 2))
    bottom = min(height, int(cy + radius + 3))
    for y in range(top, bottom):
        for x in range(left, right):
            distance = math.hypot(x + 0.5 - cx, y + 0.5 - cy) - radius
            edge = clamp(0.5 - distance)
            if edge:
                put_pixel(pixels, width, height, x, y, color, alpha * edge)


def draw_circle_rgba(pixels, width, height, cx, cy, radius, color, alpha=1.0):
    left = max(0, int(cx - radius - 2))
    right = min(width, int(cx + radius + 3))
    top = max(0, int(cy - radius - 2))
    bottom = min(height, int(cy + radius + 3))
    for y in range(top, bottom):
        for x in range(left, right):
            distance = math.hypot(x + 0.5 - cx, y + 0.5 - cy) - radius
            edge = clamp(0.5 - distance)
            if edge:
                put_pixel_rgba(pixels, width, height, x, y, color, alpha * edge)


def segment_distance(px, py, ax, ay, bx, by):
    vx = bx - ax
    vy = by - ay
    wx = px - ax
    wy = py - ay
    length_sq = vx * vx + vy * vy
    if length_sq == 0:
        return math.hypot(px - ax, py - ay)
    t = clamp((wx * vx + wy * vy) / length_sq)
    qx = ax + t * vx
    qy = ay + t * vy
    return math.hypot(px - qx, py - qy)


def draw_line(pixels, width, height, a, b, thickness, color, alpha=1.0):
    ax, ay = a
    bx, by = b
    radius = thickness / 2.0
    left = max(0, int(min(ax, bx) - radius - 2))
    right = min(width, int(max(ax, bx) + radius + 3))
    top = max(0, int(min(ay, by) - radius - 2))
    bottom = min(height, int(max(ay, by) + radius + 3))
    for y in range(top, bottom):
        for x in range(left, right):
            distance = segment_distance(x + 0.5, y + 0.5, ax, ay, bx, by) - radius
            edge = clamp(0.5 - distance)
            if edge:
                put_pixel(pixels, width, height, x, y, color, alpha * edge)


def draw_line_rgba(pixels, width, height, a, b, thickness, color, alpha=1.0):
    ax, ay = a
    bx, by = b
    radius = thickness / 2.0
    left = max(0, int(min(ax, bx) - radius - 2))
    right = min(width, int(max(ax, bx) + radius + 3))
    top = max(0, int(min(ay, by) - radius - 2))
    bottom = min(height, int(max(ay, by) + radius + 3))
    for y in range(top, bottom):
        for x in range(left, right):
            distance = segment_distance(x + 0.5, y + 0.5, ax, ay, bx, by) - radius
            edge = clamp(0.5 - distance)
            if edge:
                put_pixel_rgba(pixels, width, height, x, y, color, alpha * edge)


def draw_check(pixels, width, height, x, y, size, color):
    draw_line(pixels, width, height, (x, y + size * 0.55), (x + size * 0.32, y + size * 0.86), size * 0.13, color)
    draw_line(pixels, width, height, (x + size * 0.29, y + size * 0.84), (x + size, y), size * 0.13, color)


def draw_check_rgba(pixels, width, height, x, y, size, color):
    draw_line_rgba(pixels, width, height, (x, y + size * 0.55), (x + size * 0.32, y + size * 0.86), size * 0.13, color)
    draw_line_rgba(pixels, width, height, (x + size * 0.29, y + size * 0.84), (x + size, y), size * 0.13, color)


def draw_icon(size):
    pixels = bytearray(size * size * 3)
    top = hex_rgb('#07577a')
    bottom = hex_rgb('#0aa6a5')
    glow = hex_rgb('#55d0bf')
    navy = hex_rgb('#12304a')

    for y in range(size):
        for x in range(size):
            t = (x * 0.28 + y * 0.72) / (size - 1)
            color = mix(top, bottom, t)
            radial = 1.0 - clamp(math.hypot(x - size * 0.72, y - size * 0.18) / (size * 0.78))
            color = mix(color, glow, radial * 0.16)
            vignette = clamp(math.hypot(x - size / 2, y - size / 2) / (size * 0.78))
            color = mix(color, navy, vignette * 0.18)
            i = (y * size + x) * 3
            pixels[i:i + 3] = bytes(color)

    def s(value):
        return value * size / 1024.0

    # Soft depth behind the record card.
    draw_rounded_rect(pixels, size, size, (s(198), s(190), s(852), s(858)), s(112), hex_rgb('#062f47'), 0.20)
    draw_rounded_rect(pixels, size, size, (s(170), s(154), s(828), s(822)), s(112), hex_rgb('#edf7f6'), 1.0)

    # Header band and page body.
    draw_rounded_rect(pixels, size, size, (s(170), s(154), s(828), s(302)), s(92), hex_rgb('#d8f3ef'), 1.0)
    draw_rounded_rect(pixels, size, size, (s(204), s(220), s(794), s(790)), s(80), hex_rgb('#ffffff'), 1.0)

    # Small medical plus in a calm badge, not an ECG line.
    draw_circle(pixels, size, size, s(676), s(270), s(66), hex_rgb('#0f766e'), 1.0)
    draw_line(pixels, size, size, (s(676), s(236)), (s(676), s(304)), s(22), hex_rgb('#ffffff'), 1.0)
    draw_line(pixels, size, size, (s(642), s(270)), (s(710), s(270)), s(22), hex_rgb('#ffffff'), 1.0)

    check_green = hex_rgb('#18a48e')
    slate = hex_rgb('#728094')
    light_slate = hex_rgb('#b7c3cf')
    rows = [s(410), s(530), s(650)]
    for index, y in enumerate(rows):
        draw_rounded_rect(pixels, size, size, (s(286), y - s(38), s(360), y + s(36)), s(18), hex_rgb('#e6f6f2'), 1.0)
        draw_check(pixels, size, size, s(301), y - s(8), s(38), check_green)
        draw_rounded_rect(pixels, size, size, (s(400), y - s(32), s(690 if index != 1 else 742), y - s(8)), s(12), slate, 1.0)
        draw_rounded_rect(pixels, size, size, (s(400), y + s(14), s(646 if index != 2 else 714), y + s(36)), s(11), light_slate, 1.0)

    # Subtle bottom detail suggesting organized records.
    draw_rounded_rect(pixels, size, size, (s(286), s(724), s(738), s(754)), s(15), hex_rgb('#cfe7eb'), 1.0)
    draw_rounded_rect(pixels, size, size, (s(286), s(724), s(536), s(754)), s(15), hex_rgb('#42b9b0'), 1.0)

    return pixels


def generate(path, size):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    write_png(path, size, size, draw_icon(size))


def draw_adaptive_foreground(size):
    pixels = bytearray(size * size * 4)

    def s(value):
        return value * size / 1024.0

    draw_rounded_rect_rgba(pixels, size, size, (s(198), s(190), s(852), s(858)), s(112), hex_rgb('#062f47'), 0.20)
    draw_rounded_rect_rgba(pixels, size, size, (s(170), s(154), s(828), s(822)), s(112), hex_rgb('#edf7f6'), 1.0)
    draw_rounded_rect_rgba(pixels, size, size, (s(170), s(154), s(828), s(302)), s(92), hex_rgb('#d8f3ef'), 1.0)
    draw_rounded_rect_rgba(pixels, size, size, (s(204), s(220), s(794), s(790)), s(80), hex_rgb('#ffffff'), 1.0)

    draw_circle_rgba(pixels, size, size, s(676), s(270), s(66), hex_rgb('#0f766e'), 1.0)
    draw_line_rgba(pixels, size, size, (s(676), s(236)), (s(676), s(304)), s(22), hex_rgb('#ffffff'), 1.0)
    draw_line_rgba(pixels, size, size, (s(642), s(270)), (s(710), s(270)), s(22), hex_rgb('#ffffff'), 1.0)

    check_green = hex_rgb('#18a48e')
    slate = hex_rgb('#728094')
    light_slate = hex_rgb('#b7c3cf')
    rows = [s(410), s(530), s(650)]
    for index, y in enumerate(rows):
        draw_rounded_rect_rgba(pixels, size, size, (s(286), y - s(38), s(360), y + s(36)), s(18), hex_rgb('#e6f6f2'), 1.0)
        draw_check_rgba(pixels, size, size, s(301), y - s(8), s(38), check_green)
        draw_rounded_rect_rgba(pixels, size, size, (s(400), y - s(32), s(690 if index != 1 else 742), y - s(8)), s(12), slate, 1.0)
        draw_rounded_rect_rgba(pixels, size, size, (s(400), y + s(14), s(646 if index != 2 else 714), y + s(36)), s(11), light_slate, 1.0)

    draw_rounded_rect_rgba(pixels, size, size, (s(286), s(724), s(738), s(754)), s(15), hex_rgb('#cfe7eb'), 1.0)
    draw_rounded_rect_rgba(pixels, size, size, (s(286), s(724), s(536), s(754)), s(15), hex_rgb('#42b9b0'), 1.0)
    return pixels


def generate_adaptive_foreground(path, size):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    write_png_rgba(path, size, size, draw_adaptive_foreground(size))


def main():
    full_icon_targets = [
        ('app/assets/store/play-store-icon.png', 512),
        ('ios/App/App/public/assets/store/play-store-icon.png', 512),
        ('ios/App/App/Assets.xcassets/AppIcon.appiconset/AppIcon-512@2x.png', 1024),
        ('android/app/src/main/res/mipmap-mdpi/ic_launcher.png', 48),
        ('android/app/src/main/res/mipmap-mdpi/ic_launcher_round.png', 48),
        ('android/app/src/main/res/mipmap-hdpi/ic_launcher.png', 72),
        ('android/app/src/main/res/mipmap-hdpi/ic_launcher_round.png', 72),
        ('android/app/src/main/res/mipmap-xhdpi/ic_launcher.png', 96),
        ('android/app/src/main/res/mipmap-xhdpi/ic_launcher_round.png', 96),
        ('android/app/src/main/res/mipmap-xxhdpi/ic_launcher.png', 144),
        ('android/app/src/main/res/mipmap-xxhdpi/ic_launcher_round.png', 144),
        ('android/app/src/main/res/mipmap-xxxhdpi/ic_launcher.png', 192),
        ('android/app/src/main/res/mipmap-xxxhdpi/ic_launcher_round.png', 192),
    ]
    foreground_targets = [
        ('android/app/src/main/res/mipmap-mdpi/ic_launcher_foreground.png', 96),
        ('android/app/src/main/res/mipmap-hdpi/ic_launcher_foreground.png', 144),
        ('android/app/src/main/res/mipmap-xhdpi/ic_launcher_foreground.png', 192),
        ('android/app/src/main/res/mipmap-xxhdpi/ic_launcher_foreground.png', 288),
        ('android/app/src/main/res/mipmap-xxxhdpi/ic_launcher_foreground.png', 384),
    ]
    for relative, size in full_icon_targets:
        path = os.path.join(ROOT, relative)
        generate(path, size)
        print(path)
    for relative, size in foreground_targets:
        path = os.path.join(ROOT, relative)
        generate_adaptive_foreground(path, size)
        print(path)


if __name__ == '__main__':
    main()
