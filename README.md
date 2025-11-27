<<<<<<< HEAD
# flutter_application_1

A new Flutter project.

## Getting Started

This project is a starting point for a Flutter application.

A few resources to get you started if this is your first Flutter project:

- [Lab: Write your first Flutter app](https://docs.flutter.dev/get-started/codelab)
- [Cookbook: Useful Flutter samples](https://docs.flutter.dev/cookbook)

For help getting started with Flutter development, view the
[online documentation](https://docs.flutter.dev/), which offers tutorials,
samples, guidance on mobile development, and a full API reference.

## Integration: Central realtime database (Firestore) & SMS

This project has a local/sqflite-based data layer but also includes a
new helper at `lib/services/firestore_service.dart` that wraps Cloud
Firestore for a central realtime database accessible by both the web
and mobile apps. The repository also contains `lib/sms_service.dart`
which implements HTTP-based SMS sending through an external SMS
gateway.

Below are step-by-step instructions to set up Firebase (Firestore)
and a secure SMS sending flow using Firebase Cloud Functions. Follow
these steps to have a shared realtime backend and secure server-side
SMS sending (recommended).

1) Create a Firebase project
	 - Open https://console.firebase.google.com and create a project.
	 - Add Android, iOS and Web apps as needed (you can add multiple
		 app targets). For Android you will download `google-services.json`.
		 For iOS you'll download `GoogleService-Info.plist`. For Web you'll
		 copy the web config snippet.

2) Enable Firestore
	 - In the Firebase console, open Firestore and create a database in
		 production or test mode (test is fine during development).

3) (Recommended) Create a Cloud Function to send SMS
	 - Go to the Cloud Functions section and create a callable HTTPS
		 function named `sendSms` (or similar). This function should use
		 your SMS gateway credentials (do NOT embed these in client apps).
	 - Example function behavior: accept {message, recipients} and call
		 your HTTP SMS gateway (the same API used by `lib/sms_service.dart`).
	 - Store SMS credentials in environment variables or Secret Manager.

4) Configure local Flutter app
	 - Install Firebase CLI: `npm install -g firebase-tools` (if you don't
		 already have it).
	 - Option A (recommended): use `flutterfire` CLI to configure platforms:
			 - Install: `dart pub global activate flutterfire_cli`
			 - Run: `flutterfire configure` in the project root and follow
				 prompts to select your Firebase project and platforms. This
				 will generate the Firebase configuration files for web and
				 update native config files.
	 - Option B: Manually add platform config files: place
		 `google-services.json` (Android) under `android/app/` and
		 `GoogleService-Info.plist` (iOS) under `ios/Runner/`.

5) Update dependencies and run pub get
	 - The project `pubspec.yaml` was updated to include:
		 - `firebase_core`, `cloud_firestore`, `firebase_auth`,
			 `firebase_functions`.
	 - Run:

```powershell
flutter pub get
```

Note: if your environment blocks access to `pub.dev` (network/DNS),
you'll need to enable network access or use an alternate machine where
you can run `flutter pub get` and copy the `.pub-cache` contents.

6) Initialize Firestore usage in the app
	 - In `main.dart`, before running the app, call:

```dart
await FirestoreService.instance.init();
runApp(const MyApp());
```

7) Replace local sqflite reads/writes with Firestore calls
	 - The `lib/services/firestore_service.dart` file exposes simple
		 helpers for tasks, households, and children. Use `tasksForVHTStream`
		 to listen for real-time task updates. Example:

```dart
StreamBuilder<List<Map<String,dynamic>>>(
	stream: FirestoreService.instance.tasksForVHTStream(vhtId),
	builder: (ctx, snap) { /* ... */ }
)
```

8) Sending SMS reminders securely
	 - Prefer `requestSendSmsViaFunction(payload)` which calls your
		 Cloud Function and keeps SMS credentials server-side.
	 - If you must call an SMS gateway directly from the app, be aware
		 that credentials are exposed to users and web builds. The
		 `sendSmsDirectly` helper exists for testing but is NOT secure.

9) Scheduling reminders
	 - Use Cloud Scheduler + Cloud Functions to schedule cron-like jobs
		 that read from a `reminders` collection and invoke the SMS
		 sending flow. This keeps scheduled work off client devices.

10) Security rules
	 - Configure Firestore security rules to permit only authenticated
		 users to read/write permitted documents and to enforce role-based
		 access (VHT vs Health Worker vs Parent). Test with the Firebase
		 console rules simulator.

If you'd like, I can:
 - Add example calls in `vht_messaging.dart` or `vht_tasks.dart` to
	 demonstrate reading/writing Firestore documents and streaming
	 changes in the UI.
 - Provide a sample Cloud Function (Node.js) `sendSms` that calls the
	 existing EGOSMS API using environment variables.

Next steps I can take now:
 - Add a sample Cloud Function file (`functions/index.js`) that shows
	 how to securely send SMS and a small `firebase.json` for deployment.
 - Patch a VHT UI page to read tasks from Firestore instead of a
	 placeholder API.

## Legacy PHP backend (PROJECT/) integration

This repository contains an existing PHP/MySQL backend in the `PROJECT/`
folder. If you prefer to keep MySQL + PHP as the central server, I've
added simple REST endpoints under `PROJECT/api/` so the Flutter apps
can consume the same data.

Endpoints added (simple JSON output):
- `PROJECT/api/children.php` — lists children with parent contact info
- `PROJECT/api/followups.php` — lists follow-up tasks assigned to VHTs
- `PROJECT/api/households.php` — lists households

How to use the PHP API from Flutter (example):

```dart
final resp = await http.get(Uri.parse('https://your-server.example.com/PROJECT/api/children.php'));
if (resp.statusCode == 200) {
	final list = jsonDecode(resp.body) as List;
	// map into your app models
}
```

Notes on choosing a central backend:
- Firestore: best for realtime updates, simpler client integration (Flutter web+mobile). Recommended if you can use Firebase.
- PHP/MySQL (legacy): works fine; keep server-side SMS logic and business rules in PHP. Flutter apps can consume JSON REST endpoints.

If you want, I can:
- Convert more Flutter pages to call the PHP API (instead of Firestore),
	or add a toggle so the app can switch between Firestore and PHP REST.
- Add authentication for the PHP API (JWT or session-based) to secure endpoints.

=======
# CAREVAX
>>>>>>> 2ede70d363bb0743d6effae62139e9f429c557bf
