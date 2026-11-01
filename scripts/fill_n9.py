#!/usr/bin/env python3
"""Fill the N9 Move Out template and FLATTEN to a static PDF.

Usage: fill_n9.py <template.pdf> <output.pdf> '<json-values>'
JSON keys match the AcroForm field names in the template.

Instead of relying on the PDF viewer to re-render form fields (which
mangles auto-sized date boxes), this draws the values directly onto the
page as static text with explicit font sizes and removes all form
widgets — output renders identically in every viewer, and the original
"email to us" submit button is gone.
"""
import json
import sys

import pymupdf

# field name -> (font, size). Courier matches the template's DD/MM/YYYY
# and name fields; Helvetica matches the signature/date fields.
FIELD_STYLE = {
    "DD": ("cour", 10),
    "MM": ("cour", 10),
    "YYYY": ("cour", 10),
    "First Name": ("cour", 11),
    "Last Name": ("cour", 11),
    "undefined_2": ("cour", 10),
    "undefined_3": ("cour", 10),
    "undefined_4": ("cour", 10),
    "To Landlords nameRow1": ("cour", 11),
    "From Tenants name include all tenant namesRow1": ("cour", 11),
    "Address of the Rental UnitRow1": ("cour", 11),
    "Signature": ("helv", 12),
    "Date ddmmyyyy": ("helv", 12),
}


def main() -> int:
    template_path, output_path, values_json = sys.argv[1], sys.argv[2], sys.argv[3]
    values = json.loads(values_json)

    doc = pymupdf.open(template_path)

    for page in doc:
        for widget in list(page.widgets()):
            name = widget.field_name
            rect = widget.rect  # pymupdf coords (top-left origin)

            # Red "email it to us" submit button — drop it entirely.
            if name == "Submit":
                page.delete_widget(widget)
                continue

            # Tenant radio — draw an X in the box instead of a form field.
            if name == "Tenant" and values.get("Tenant") == "/On":
                cx = (rect.x0 + rect.x1) / 2
                cy = (rect.y0 + rect.y1) / 2
                page.insert_text(pymupdf.Point(cx - 3.5, cy + 3), "X",
                                 fontname="hebo", fontsize=10)
                page.delete_widget(widget)
                continue

            # Draw each value as static text, vertically centered in its box.
            if name in FIELD_STYLE and values.get(name) not in (None, ""):
                font, size = FIELD_STYLE[name]
                x = rect.x0 + 2
                baseline = rect.y0 + (rect.height - size) / 2 + size * 0.8
                page.insert_text(pymupdf.Point(x, baseline), str(values[name]),
                                 fontname=font, fontsize=size)

            # Remove the form field so nothing depends on viewer rendering.
            page.delete_widget(widget)

    doc.save(output_path, garbage=3, deflate=True)
    doc.close()

    print(json.dumps(values))
    return 0


if __name__ == "__main__":
    sys.exit(main())
