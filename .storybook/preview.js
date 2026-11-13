/** @type {import('@storybook/server-webpack5').Preview} */
const preview = {
  parameters: {
    server: {
      url: `${window.location.protocol}//${window.location.hostname}:6007`,
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
