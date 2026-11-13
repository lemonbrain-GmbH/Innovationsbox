/*jslint       browser: true, continue: true,
    devel: true, indent: 2, maxerr: 50,
    newcap: true, nomen: true, plusplus: true,
    regexp: true, sloppy: true, vars: false,
    white: true
 */

/*global window: true */
interface Window {
  spa: any
}

enum ELEMENTS {
  ICON = 'i',
  SPAN = 'span',
  META = 'meta'
}

enum CLASSES {
  SCREEN_READER_ONLY = 'sr-only'
}

enum MISC {
  DELIMITER = ' '
}

class MailAddressWrapper {
  constructor() {
    const mailAddressElements = document.querySelectorAll('a[data-mail-unveil="true"]');

    mailAddressElements.forEach(addr => {
      new MailAddress(addr);
    });
  }
}

class MailAddress {
  constructor(element: Element) {
    this.helper(
      element.getAttribute('data-mail-obfuscated') || '',
      element.getAttribute('data-mail-unique-id') || '',
      element.getAttribute('data-mail-title') || '',
      element.getAttribute('data-mail-inner-text') || '',
      element.getAttribute('data-mail-link-classes') || '',
      element.getAttribute('data-mail-additional-href-params') || '',
      element.getAttribute('data-mail-meta-item-prop') || ''
    );
  }

  _strtr(input: string, source: string, replace: string): string {
    let result = ""
    for (let i = 0; i < input.length; i++) {
      let index = source.indexOf(input[i])
      result += replace[index]
    }

    return result
  }

  rot47(text: string): string {
    return this._strtr(
      text,
      'PQRSTUVWXYZ^_`abcdefghijklmnopqrstuvwxyz{|}~!#$%&\'()*+,-./0123456789:;=?@ABCDEFGHIJKLMNO',
      '!#$%&\'()*+,-./0123456789:;=?@ABCDEFGHIJKLMNOPQRSTUVWXYZ^_`abcdefghijklmnopqrstuvwxyz{|}~'
    )
  }

  helper(
    obfuscated: string,
    uniqueID: string,
    title: string,
    innerText: string,
    linkClasses: string,
    additionalHrefParams: string,
    metaItemProp: string,
  ) {
    const mailAnchorElement = document.querySelector<HTMLAnchorElement>(`.mail-${uniqueID}`)
    let meta: HTMLMetaElement | undefined;

    if (!mailAnchorElement) {
      console.error(`No anchor element found for unique ID: ${uniqueID}`);
      return;
    }

    if (innerText !== '') {
      mailAnchorElement.innerText = innerText
    } else {
      mailAnchorElement.innerText = this.rot47(obfuscated)
    }

    let href = "mailto:" + this.rot47(obfuscated)
    if (additionalHrefParams) {
      href += additionalHrefParams
    }
    mailAnchorElement.href = href
    if (title === '') {
      mailAnchorElement.title = this.rot47(obfuscated)
    }

    if (metaItemProp) {
      meta = document.createElement(ELEMENTS.META)
      meta.setAttribute('itemprop', metaItemProp)
      meta.setAttribute('content', this.rot47(obfuscated))

      mailAnchorElement.appendChild(meta)
    }

    if (linkClasses && linkClasses.length > 0) {
      const linkClassArr = linkClasses.split(MISC.DELIMITER)
      linkClassArr.forEach(linkCls => {
        mailAnchorElement.classList.add(linkCls)
      })
    }
  }
}

window.addEventListener('DOMContentLoaded', () => new MailAddressWrapper())
