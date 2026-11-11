#!/usr/bin/env python3
"""Build the front CSS (no Node needed).

1. Neutral selectors (E7): every selector keyed on a 1.x Elementor widget class (.elementor-widget-bpb-*) gets an
   alias keyed on the element class (.bpb-el-<element>) printed by both builders, so the same rule styles blocks
   and Elementor widgets. Elementor-only selectors (.elementor-element, .elementor-widget-container...) are not
   aliased. Aliases are generated between markers and regenerated on every run (idempotent).
2. Minify each source into <name>.min.css: with lightningcss when BPB_LIGHTNINGCSS points to its CLI
   (npm i lightningcss-cli), otherwise with a plain whitespace minifier.

Usage: [BPB_LIGHTNINGCSS=/path/to/lightningcss] bin/build-css.py
"""
import json
import os
import pathlib
import re
import subprocess

ROOT = pathlib.Path(__file__).resolve().parent.parent
SOURCES = [ROOT / "assets/css/index.css", ROOT / "assets/css/buddyboss.css"]
ELEMENTOR_ONLY = re.compile(r"\.elementor-(element|absolute|widget-container|column|section|container)\b")


def legacy_map():
    out = {}
    for path in sorted((ROOT / "src/Elements").glob("*/element.json")):
        data = json.loads(path.read_text())
        for widget in data.get("legacy", {}).get("elementor", []):
            out[widget] = data["name"]
    return out


def alias_selector(selector, mapping):
    if ELEMENTOR_ONLY.search(selector):
        return None
    found = False

    def repl(match):
        nonlocal found
        name = match.group(1)
        if name in mapping:
            found = True
            return ".bpb-el-" + mapping[name]
        return match.group(0)

    out = re.sub(r"\.elementor-widget-(bpb-[a-z0-9-]+)", repl, selector)
    return out if found else None


def strip_old_aliases(css):
    return re.sub(r"/\*bpb-alias\*/[^{]*?/\*/bpb-alias\*/ ?", "", css)


def add_aliases(css, mapping):
    css = strip_old_aliases(css)
    out, i, depth, start = [], 0, 0, 0
    while i < len(css):
        c = css[i]
        if css.startswith("/*", i):
            end = css.find("*/", i + 2)
            i = len(css) if end < 0 else end + 2
            continue
        if c == "{":
            prelude = css[start:i]
            stripped = prelude.strip()
            if stripped and not stripped.startswith("@"):
                selectors = [s.strip() for s in stripped.split(",")]
                aliases = [a for a in (alias_selector(s, mapping) for s in selectors) if a and a not in selectors]
                if aliases:
                    lead = prelude[: len(prelude) - len(prelude.lstrip())]
                    prelude = lead + stripped + "/*bpb-alias*/," + ",".join(aliases) + "/*/bpb-alias*/ "
            out.append(prelude + "{")
            depth += 1
            start = i + 1
        elif c == "}":
            out.append(css[start : i + 1])
            depth -= 1
            start = i + 1
        elif c == ";" and depth == 0:
            out.append(css[start : i + 1])
            start = i + 1
        i += 1
    out.append(css[start:])
    return "".join(out)


def minify(css):
    css = re.sub(r"/\*(?!!).*?\*/", "", css, flags=re.S)
    css = re.sub(r"\s+", " ", css)
    css = re.sub(r"\s*([{};:,>])\s*", r"\1", css)
    css = css.replace(";}", "}")
    return css.strip() + "\n"


def main():
    mapping = legacy_map()
    for src in SOURCES:
        css = add_aliases(src.read_text(), mapping)
        src.write_text(css)
        target = src.with_suffix("").with_suffix(".min.css")
        tool = os.environ.get("BPB_LIGHTNINGCSS")
        if tool:
            subprocess.run([tool, "--minify", "--targets", "defaults", str(src), "-o", str(target)], check=True)
        else:
            target.write_text(minify(css))
        print(src.relative_to(ROOT), "aliases:", css.count("/*bpb-alias*/"))


if __name__ == "__main__":
    main()
