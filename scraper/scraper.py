# Imports
from lxml import html
import json

# Set tree variable
tree = None

# Open index.html file.
with open('./../index.html') as f:
    tree = html.document_fromstring(f.read())

# Grab all panels.
panels = tree.xpath('//div[@role="tabpanel"]')

output = {}

# For each panel, key is the id value.
for panel in panels:

    output[panel.attrib['id']] = []

    # Get all headers - high level ideas.
    headers = panel.xpath('//thead')

    # Iterate through headers and add, prepare to add points.
    for header in headers:
        output[panel.attrib['id']].append({
            "header": header.text_content().replace('\n', '').replace('\r', '').strip(),
            "points": []
        })

        # Retrieve the next body element and append all points.
        body = header.getnext()
        output[panel.attrib['id']][len(output[panel.attrib['id']]) - 1]['points'].extend([x.text_content().replace('\n', '').replace('\r', '').strip() for x in body.xpath('tr/td')])

# Dump into a file.
with open('data.json', 'w') as f:
    f.write(json.dumps(output))


