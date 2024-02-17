/** @type {import("prettier").Config} */
const config = {
  semi: true,
  singleQuote: true,
  overrides: [
    {
      files: "*.php",
      options: {
        plugins: ["@prettier/plugin-php"],
        parser: "php",
        phpVersion: "8.2",
        braceStyle: "per-cs",
        trailingCommaPHP: true,
      },
    },
  ],
};

export default config;
