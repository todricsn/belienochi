# ImageGen prompt set — premium gallery v2

Mode: built-in `image_gen`, `precise-object-edit`. Each of the 16 assets was edited in a separate ImageGen call. PNG files are generated masters; WebP files are quality-92 delivery assets.

## Shared prompt

Use case: precise-object-edit. The attached image is the exact real room/property and the sole visual authority. Create a highly faithful professional full-frame hospitality photograph of this exact scene with convincing 4K-grade micro-detail and natural realism. Correct only camera roll/horizon, vertical and keystone perspective, mild lens distortion, white balance, exposure, dynamic range, color cast, local contrast, texture clarity and sensor noise. Preserve real material texture and credible wear. Use balanced natural-looking light, controlled highlights, soft realistic shadows and restrained professional color grading.

Absolute preservation: keep the exact identity, architecture, dimensions, viewpoint, framing, walls, ceiling, floor, windows, doors, bed count, furniture, curtains, lamps, TVs, tables, cabinets, outlets, decor, textiles, patterns, colors and every object's position. Do not redesign, renovate, restage, rearrange, add, remove, replace, clean away, enlarge, shrink or invent anything. Do not make a different or more luxurious place. Do not invent hidden areas. Minimal edge crop is allowed only for true horizon/vertical correction. For portrait inputs, preserve the source aspect ratio and orientation and do not outpaint.

Avoid plastic HDR, excessive saturation, halos, fake sharpness, CGI, blur, altered textiles, altered furniture, new decor, fake views, generated text, logos or watermarks.

## Source and output map

1. `marali-room-01.png` → `premium-room-01.png`: lilac double room; preserve the bed, two cabinets and lamps, damask curtains, floral bedspread, towels, floor and narrow left cabinet.
2. `marali-room-02.png` → `premium-room-02.png`: twin room; preserve exactly two beds, chair, desk, TV and cables, striped curtains, flag, foreground table and all placements.
3. `marali-room-03.png` → `premium-room-03.png`: green double room; preserve the floral panels, bed, two cabinets and lamps, curtain, textiles and floor.
4. `marali-room-04.png` → `premium-room-04.png`: corridor; preserve every door, wall panel, ceiling tile/light, floor tile, far door and exact vanishing point.
5. `marali-room-05.png` → `premium-room-05.png`: seating/work zone; preserve two armchairs, tables, desk, chair, mini-fridge, curtains, doorway and floor.
6. `marali-room-06.png` → `premium-room-06.png`: red room; preserve the partial bed, radiator, olive curtains, red seating, table, cushion and asymmetrical framing.
7. `codex-clipboard-17a07222-b9e9-4ac3-b025-a07823e23fb7.png` → `premium-new-07.png`: burgundy family room; preserve one double plus two single beds, windows, desk, TV, door and glossy ceiling; correct strong wide-angle tilt.
8. `codex-clipboard-c0723fe8-980c-4774-b54a-b49f9957d272.png` → `premium-new-08.png`: banquet zone; preserve the real decoration, Cyrillic balloon phrase, all balloons, green light, furniture, table contents and partially visible guest.
9. `codex-clipboard-c68bc21a-8257-4c3e-bec7-4159e3f7fbe0.png` → `premium-new-09.png`: green bed detail; preserve the bed, wallpaper, headboard, pillows, textiles, towels, lamp and cabinet.
10. `codex-clipboard-cabcb5d1-da26-4ddf-bfc6-1c8a7236dd36.png` → `premium-new-10.png`: sauna; preserve the small real dimensions, two bench levels, every slat, wood wear, lamp cover, door edge and floor tile.
11. `codex-clipboard-50c3cbcd-d696-45ae-8661-93cc8e7ba86e.png` → `premium-new-11.png`: seating detail; preserve two chairs, table, kettle/glasses, TV, wardrobe, wallpaper and partial bed.
12. `codex-clipboard-5ccce169-ee49-4799-b002-85f866cfa905.png` → `premium-new-12.png`: long burgundy room; preserve one bed, TV, window, red seating, table, ceiling reflection, fireplace edge and footwear.
13. `codex-clipboard-bdcd7958-2eba-44d7-b272-d5860e4b276a.png` → `premium-new-13.png`: pink twin room; preserve exactly two beds, red ceiling, window, curtains, table, cabinet, kettle and TV.
14. `codex-clipboard-5647e4ab-6349-4f6e-b68d-048f64610d5e.png` → `premium-new-14.png`: light family room; preserve one double plus two single beds including the cropped bed, ceiling, window, wallpaper and linens.
15. `codex-clipboard-83092044-4935-4032-afbb-39fb389319ff.png` → `premium-new-15.png`: large red room; preserve the exact bed textiles, red sofa/chair, decorative fireplace, wall panels, doorway, ceiling and foreground table.
16. `codex-clipboard-687b743a-b76f-4a91-a416-99fac07640da.png` → `premium-new-16-v2.png` → `premium-new-16-v3.png`: real hotel entrance; preserve the literal `ГОСТИНИЦА` sign, facade, entrance, windows and accessible ramp. First pass adds professional late-afternoon architectural light and crops away the unfinished neighboring structure without rebuilding it; second pass only tightens the 16:9 framing to remove the empty white edge.

The six older `marali-room-*` assets remain in place as an untouched fallback. The live gallery uses the versioned `premium-*` WebP files.
