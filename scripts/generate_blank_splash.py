#!/usr/bin/env python3
import os
import struct
import zlib


ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
WHITE = (255, 255, 255)


def chunk(name, data):
    payload = name + data
    return struct.pack('>I', len(data)) + payload + struct.pack('>I', zlib.crc32(payload) & 0xFFFFFFFF)


def write_png(path, width, height, color=WHITE):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    row = b'\x00' + bytes(color) * width
    raw = row * height
    with open(path, 'wb') as handle:
        handle.write(b'\x89PNG\r\n\x1a\n')
        handle.write(chunk(b'IHDR', struct.pack('>IIBBBBB', width, height, 8, 2, 0, 0, 0)))
        handle.write(chunk(b'IDAT', zlib.compress(raw, 9)))
        handle.write(chunk(b'IEND', b''))


def main():
    targets = [
        ('ios/App/App/Assets.xcassets/Splash.imageset/splash-2732x2732.png', 2732, 2732),
        ('ios/App/App/Assets.xcassets/Splash.imageset/splash-2732x2732-1.png', 2732, 2732),
        ('ios/App/App/Assets.xcassets/Splash.imageset/splash-2732x2732-2.png', 2732, 2732),
        ('android/app/src/main/res/drawable/splash.png', 480, 320),
        ('android/app/src/main/res/drawable-land-mdpi/splash.png', 480, 320),
        ('android/app/src/main/res/drawable-land-hdpi/splash.png', 800, 480),
        ('android/app/src/main/res/drawable-land-xhdpi/splash.png', 1280, 720),
        ('android/app/src/main/res/drawable-land-xxhdpi/splash.png', 1600, 960),
        ('android/app/src/main/res/drawable-land-xxxhdpi/splash.png', 1920, 1280),
        ('android/app/src/main/res/drawable-port-mdpi/splash.png', 320, 480),
        ('android/app/src/main/res/drawable-port-hdpi/splash.png', 480, 800),
        ('android/app/src/main/res/drawable-port-xhdpi/splash.png', 720, 1280),
        ('android/app/src/main/res/drawable-port-xxhdpi/splash.png', 960, 1600),
        ('android/app/src/main/res/drawable-port-xxxhdpi/splash.png', 1280, 1920),
    ]

    for relative, width, height in targets:
        path = os.path.join(ROOT, relative)
        write_png(path, width, height)
        print(path)


if __name__ == '__main__':
    main()
