# LMS MOE Mobile

Standalone Flutter foundation for the primary-school LMS. Everything in this directory is isolated from the live Laravel web application.

## Current preview

The app follows the live LMS visual tokens: teal branding, soft grey page background, rounded cards, Malay interface text, and separate Student and Teacher homes.

It starts in preview authentication mode so it does not call or modify the live website:

| Username | Any password | Result |
| --- | --- | --- |
| `murid` or `student` | Accepted | Student home for Tahun 4 |
| `guru`, `cikgu`, or `teacher` | Accepted | Teacher dashboard |

Run it with:

```powershell
cd mobile
flutter run
```

## Later API connection

The API client is prepared but disabled by default. Once an approved mobile API exists, run with:

```powershell
flutter run --dart-define=PREVIEW_AUTH=false --dart-define=API_BASE_URL=https://your-api.example/api
```

The API must return a signed-in user with a `role` field of `student` or `teacher`. The Flutter role router then opens the correct application surface.

## Validation

```powershell
flutter analyze
flutter test
```
