/// Preview (demo) authentication. The real mobile API is live now, so this
/// defaults to false and the app uses real login. Pass --dart-define=PREVIEW_AUTH=true
/// only if you deliberately want the offline demo login again.
const usePreviewAuthentication = bool.fromEnvironment(
  'PREVIEW_AUTH',
  defaultValue: false,
);
