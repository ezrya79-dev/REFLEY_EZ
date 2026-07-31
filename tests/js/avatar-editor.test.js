import { describe, expect, it } from 'vitest';
import { clampOffset, coverScale, EDITOR_SIZE } from '../../resources/js/avatar-editor.js';

describe('coverScale', () => {
    it('scales a landscape image so its height covers the frame', () => {
        expect(coverScale(512, 256)).toBe(1);
    });

    it('scales a portrait image so its width covers the frame', () => {
        expect(coverScale(256, 512)).toBe(1);
    });

    it('upscales small images', () => {
        expect(coverScale(128, 128)).toBe(2);
    });

    it('falls back to 1 for degenerate sizes', () => {
        expect(coverScale(0, 100)).toBe(1);
    });
});

describe('clampOffset', () => {
    it('never allows a positive offset (gap on the leading edge)', () => {
        expect(clampOffset(50, 512, 1)).toBe(0);
    });

    it('never lets the trailing edge leave a gap', () => {
        expect(clampOffset(-9999, 512, 1)).toBe(EDITOR_SIZE - 512);
    });

    it('keeps valid offsets untouched', () => {
        expect(clampOffset(-100, 512, 1)).toBe(-100);
    });
});
