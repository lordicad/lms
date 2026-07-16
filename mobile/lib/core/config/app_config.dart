/// Keeps the standalone Flutter prototype safe to run without changing the
/// live Laravel application. Set to false only after an approved mobile API is
/// available.
const usePreviewAuthentication = bool.fromEnvironment(
  'PREVIEW_AUTH',
  defaultValue: true,
);
