/** @type {import('@storybook/server-webpack5').Preview} */

let prerenderedData = null;

async function getPrerenderedData() {
  if (prerenderedData !== null) return prerenderedData;
  try {
    const res = await fetch('./prerendered.json');
    if (res.ok) {
      prerenderedData = await res.json();
    } else {
      prerenderedData = {};
    }
  } catch {
    prerenderedData = {};
  }
  return prerenderedData;
}

const isStaticDeployment =
  typeof window !== 'undefined' &&
  !['localhost', '127.0.0.1', '0.0.0.0'].includes(window.location.hostname);

const preview = {
  parameters: {
    server: {
      url: `${window.location.protocol}//${window.location.hostname}:6007`,
      ...(isStaticDeployment && {
        fetchStoryHtml: async (url, id, params) => {
          const data = await getPrerenderedData();
          const key = `${id}|${JSON.stringify(params)}`;
          const keyNoArgs = `${id}|{}`;
          return data[key] || data[keyNoArgs] || `<div style="color:#666;padding:20px;">Component preview not available in static mode.</div>`;
        },
      }),
    },
    a11y: {
      config: {
        rules: [
          { id: 'landmark-one-main', enabled: false },
          { id: 'page-has-heading-one', enabled: false },
          { id: 'region', enabled: false },
        ],
      },
      manual: false,
    },
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
    },
  },
};

export default preview;
