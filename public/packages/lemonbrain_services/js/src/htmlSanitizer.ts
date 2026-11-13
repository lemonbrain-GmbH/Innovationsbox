export interface SanitizeOptions {
  allowedTags?: string[];
  allowedAttrs?: {
    [tag: string]: string[];
  };
}

export function sanitizeHTML(
  html: string,
  {
    allowedTags = ['b', 'i', 'em', 'strong', 'u', 'p', 'br', 'ul', 'ol', 'li', 'a', 'span'],
    allowedAttrs = {
      'a': ['href', 'title', 'target', 'rel'],
      'span': ['class'],
      '*': [] // attributes allowed on all elements
    }
  }: SanitizeOptions = {}
): string {
  const parser = new DOMParser();
  const doc = parser.parseFromString(html, 'text/html');

  const sanitizeNode = (node: Node): void => {
    if (node.nodeType === Node.ELEMENT_NODE) {
      const el = node as HTMLElement;
      const tag = el.tagName.toLowerCase();

      if (!allowedTags.includes(tag)) {
        el.replaceWith(...Array.from(el.childNodes));
        console.info(`lbServices HTML Sanitizer: Removed disallowed tag: <${tag}>`);
        return;
      }

      for (const attr of Array.from(el.attributes)) {
        const name = attr.name.toLowerCase();
        const globalAllowed = allowedAttrs['*'] || [];
        const tagAllowed = allowedAttrs[tag] || [];

        const allowed =
          globalAllowed.includes(name) ||
          tagAllowed.includes(name);

        if (!allowed) {
          el.removeAttribute(name);
          console.info(`lbServices HTML Sanitizer: Removed disallowed attribute: ${name} from <${tag}>`);
          continue;
        }

        if (
          (name === 'href' || name === 'src') &&
          attr.value.trim().toLowerCase().startsWith('javascript:')
        ) {
          el.removeAttribute(name);
          console.info(`lbServices HTML Sanitizer: Removed ${name} attribute from <${tag}>`);
        }
      }
    }

    for (const child of Array.from(node.childNodes)) {
      sanitizeNode(child);
    }
  };

  for (const child of Array.from(doc.body.childNodes)) {
    sanitizeNode(child);
  }

  return doc.body.innerHTML;
}

export function sanitizePictureHTML(html: string): string {
  return sanitizeHTML(html, {
    allowedTags: ['div', 'picture', 'source', 'img'],
    allowedAttrs: {
      'div': ['class', 'style'],
      'source': ['media', 'type', 'srcset', 'sizes'],
      'img': ['src', 'alt', 'class'],
      '*': []
    }
  });
}
