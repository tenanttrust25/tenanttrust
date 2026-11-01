#!/usr/bin/env python3
"""Fill an LTB-style AcroForm PDF template and FLATTEN to a static PDF.

Usage: fill_form.py <template.pdf> <output.pdf> '<values-json>' ['<styles-json>']

values-json: {"widget_name": "text"} for text fields; {"widget_name": "X"}
for checkboxes / radio options. Widgets that share a field name (radio
groups, e.g. SelectSign appearing twice) are targeted with "name#0",
"name#1" — the nth widget on that page with that name.

styles-json (optional): {"widget_name": ["cour", 10]} font/size overrides.
Defaults: Courier 10 for text, Helvetica-Bold 10 for the X mark.

Long text values are word-wrapped to the widget width (up to the widget
height); values containing "\\n" are drawn as stacked lines. All form
widgets are removed afterwards so output renders identically everywhere.
"""
import json
import re
import sys

import pymupdf

DEFAULT_FONT = "cour"
DEFAULT_SIZE = 10
X_FONT = "hebo"
X_SIZE = 12


def lookup_keys(name: str) -> list[str]:
    """Return lookup keys for a widget: full field name, then a short form.

    The N4/N5 templates nest every field under "form1[0].#subform[N]." and
    append "[0]" to each segment; N1/N11/N9 use short names directly. The PHP
    maps use short names everywhere, so fall back to the stripped key.
    """
    keys = [name]
    short = re.sub(r"^form1\[0\]\.#subform\[\d+\]\.", "", name)
    short = short.replace("[0]", "")
    if short and short != name:
        keys.append(short)
    # Last path segment, e.g. "...Notice_Name_and_Address[0].TO_TenameName[0]" -> "TO_TenameName"
    last = short.rsplit(".", 1)[-1] if short else name
    if last and last not in keys:
        keys.append(last)
    return keys


def wrap_text(text: str, font: str, size: float, max_width: float, max_lines: int) -> list[str]:
    """Word-wrap text to fit max_width, capped at max_lines."""
    words = text.split()
    if not words:
        return []
    lines: list[str] = []
    current = ""
    for word in words:
        trial = word if not current else f"{current} {word}"
        width = pymupdf.get_text_length(trial, fontname=font, fontsize=size)
        if current and width > max_width:
            lines.append(current)
            current = word
        else:
            current = trial
    if current:
        lines.append(current)
    if len(lines) > max_lines:
        lines = lines[:max_lines]
        # trim last line and add ellipsis
        last = lines[-1]
        while last and pymupdf.get_text_length(last + "…", fontname=font, fontsize=size) > max_width:
            last = last[:-1]
        lines[-1] = last.rstrip() + "…"
    return lines


def main() -> int:
    template_path, output_path, values_json = sys.argv[1], sys.argv[2], sys.argv[3]
    styles_json = sys.argv[4] if len(sys.argv) > 4 else "{}"
    values = json.loads(values_json)
    styles = json.loads(styles_json)

    doc = pymupdf.open(template_path)

    for page in doc:
        widgets = list(page.widgets())
        counts: dict[str, int] = {}
        for w in widgets:
            name = w.field_name
            if name:
                counts[name] = counts.get(name, 0) + 1

        seen: dict[str, int] = {}
        for widget in widgets:
            name = widget.field_name
            if not name:
                page.delete_widget(widget)
                continue

            idx = seen.get(name, 0)
            seen[name] = idx + 1
            indexed = f"{name}#{idx}" if counts[name] > 1 else name
            keys = lookup_keys(name)
            if counts[name] > 1:
                keys = [k + f"#{idx}" for k in keys]
            value = next((values.get(k) for k in keys if values.get(k) is not None), None)

            rect = widget.rect
            ftype = widget.field_type  # 1 button, 2 checkbox, 5 radio, 7 text

            if ftype in (2, 5):  # checkbox / radio
                if value is not None and str(value).strip().upper() in ("X", "/X", "ON", "YES", "TRUE", "1"):
                    cx = (rect.x0 + rect.x1) / 2
                    cy = (rect.y0 + rect.y1) / 2
                    page.insert_text(pymupdf.Point(cx - 3.5, cy + 3), "X",
                                     fontname=X_FONT, fontsize=X_SIZE)
            elif ftype == 7 and value not in (None, ""):  # text field
                font, size = styles.get(name, (DEFAULT_FONT, DEFAULT_SIZE))
                text = str(value)
                lines = text.split("\n") if "\n" in text else wrap_text(
                    text, font, size, rect.width - 4, max(1, int(rect.height / (size * 1.15)))
                )
                if not lines:
                    page.delete_widget(widget)
                    continue
                x = rect.x0 + 2
                if len(lines) == 1:
                    baseline = rect.y0 + (rect.height - size) / 2 + size * 0.8
                    page.insert_text(pymupdf.Point(x, baseline), lines[0],
                                     fontname=font, fontsize=size)
                else:
                    line_height = size * 1.15
                    total = line_height * len(lines)
                    y = rect.y0 + (rect.height - total) / 2 + size * 0.85
                    for line in lines:
                        page.insert_text(pymupdf.Point(x, y), line,
                                         fontname=font, fontsize=size)
                        y += line_height
            # button widgets (1) and anything else: flatten/drop

            page.delete_widget(widget)

    doc.save(output_path, garbage=3, deflate=True)
    doc.close()
    print(json.dumps(values))
    return 0


if __name__ == "__main__":
    sys.exit(main())
