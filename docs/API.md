# Wildlife Tracking API

Base URL: `http://127.0.0.1:8000/api` (juga tersedia dengan prefix versi: `/api/v1/...`).
Dokumentasi interaktif (otomatis dari kode): `GET /docs/api` (Scramble, mode lokal).

## Setup

```bash
composer install
cp .env.example .env && php artisan key:generate
# isi DB_* (MySQL) dan GROQ_API_KEY di .env
php artisan migrate:fresh --seed
php artisan serve
```

Skema migrasi dirapikan ulang, jadi database lama perlu `migrate:fresh --seed`.
Akun seed (password `password123`): `admin@`, `peneliti@`, `lapangan@`, `kebijakan@wildlife.id`.

## Format respons

```json
{ "success": true, "message": "OK", "data": ..., "errors": null }
```

- Endpoint list mengembalikan **array polos** di `data`. Pagination opsional lewat `?page=&per_page=`
  dengan metadata di header `X-Total-Count`, `X-Current-Page`, `X-Per-Page`, `X-Last-Page`.
- Sort: `?orderBy=<kolom>&order=asc|desc` (kolom di-whitelist per endpoint).
- Error: `401` belum login, `403` tidak punya permission, `404` data tidak ada, `409` bentrok
  relasi/duplikat, `422` validasi (`errors` berisi detail per field).
- Relasi dikirim dengan nama camelCase sesuai frontend (`trackingDevice`, `predictedSpecies`, `weatherData`, ...).

## Perubahan skema (hasil audit)

| Tabel | Sebelum | Sesudah |
|---|---|---|
| users | `name`, `role` (enum teks) | `user_id, role_id → roles, username, full_name, address, phone, status` |
| roles | `role_name` | `role_id, name_role` |
| permissions | `permission_name` | `permission_id, name_permission` |
| role_permissions | PK gabungan | `role_permission_id` + unique(role_id, permission_id) |
| animals | `name` | `animal_id, name_animal` (+ `distinctive_marks` untuk identifikasi AI) |
| incident_reports | `animal_code` (teks), `location` (nama zona) | `user_id, animal_id, species_id, zone_id` (FK) |
| tracking_devices | hanya GPS | `device_type` = `gps`/`camera`/`audio`, `zone_id`, `latitude`, `longitude`, `serial_number` |
| ai_media_uploads | gambar/video | + `audio`, `device_id`, `zone_id`, `latitude`, `longitude`, `captured_at`, `mime_type` |
| ai_predictions | spesies + confidence | + individu, kondisi fisik, perilaku, kategori suara, tingkat ancaman |

Semua relasi antar tabel memakai ID (FK), bukan nama.

**Alias kompatibilitas frontend** (tetap dikirim agar FE tidak perlu diubah):
`animal.name` = `name_animal`, `role.role_name` = `name_role`, `permission_name` = `name_permission`,
`incident.reported_by` = `user_id`, `species.animal_count`. Request juga menerima `name` (animals),
`role` / `role_name` (users/roles).

## Permission per role

| Permission | konservasionis | peneliti | admin | kebijakan |
|---|:-:|:-:|:-:|:-:|
| view_data | ✓ | ✓ | ✓ | ✓ |
| create_data / update_data | ✓ | ✓ | ✓ | |
| upload_media | ✓ | ✓ | ✓ | ✓ |
| verify_ai | ✓ | ✓ | ✓ | |
| manage_master_data | | ✓ | ✓ | |
| view_reports / export_data | | ✓ | ✓ | ✓ |
| delete_data / manage_users / manage_roles | | | ✓ | |

Permission disimpan di `role_permissions` dan bisa diubah admin lewat `/roles/{id}/permissions`.

## Endpoint

Alias path: `devices` = `tracking-devices`, `incidents` = `incident-reports`,
`login` = `auth/login`, `logout` = `auth/logout`, `me` = `auth/me` = `users/me`.

### Auth & profil
| Method | Path | Keterangan |
|---|---|---|
| POST | `/login` | `{email, password}` → `{token, user}` (rate limit) |
| POST | `/logout` | cabut token aktif |
| POST | `/auth/refresh` | token baru |
| GET | `/me` | profil + role + permissions |
| PATCH | `/me` | ubah profil sendiri (tidak bisa ubah role) |
| PUT | `/me/password` | `{old_password, new_password, new_password_confirmation}` |

### Users & roles (admin)
`GET/POST /users`, `GET/PUT/DELETE /users/{id}`, `PATCH /users/{id}/status`, `GET /users/{id}/activity`,
`GET /users/{id}/tokens`, `DELETE /users/{id}/tokens/{tokenId}`,
`GET/POST /roles`, `GET/PUT/DELETE /roles/{id}`, `GET/PUT /roles/{id}/permissions`,
`POST/DELETE /roles/{id}/permissions/{permissionId}`, `GET /permissions`, `GET /permissions/{id}` (read-only),
`GET /activity-logs`, `GET /activity-logs/{id}` (read-only).

### Master data
| Resource | Endpoint |
|---|---|
| Species | `GET/POST /species`, `GET/PUT/DELETE /species/{id}` (hapus ditolak 409 jika masih punya satwa) |
| Animals | `GET/POST /animals`, `GET/PUT/DELETE /animals/{id}`, `PATCH /animals/{id}/status`, `GET /animals/{id}/track`, `/zone-visits`, `/incidents`, `/ai-observations` |
| Devices | `GET/POST /devices` (`?device_type=gps,camera,audio`), `GET/PUT/DELETE /devices/{id}`, `PATCH/DELETE /devices/{id}/assign-animal`, `PATCH /devices/{id}/condition`, `/status`, `/telemetry`, `GET /devices/{id}/latest-reading`, `/gps-readings`, `/battery-history`, `/captures`, `POST /devices/{id}/captures` |
| Geo zones | `GET/POST /geo-zones`, `GET/PUT/DELETE /geo-zones/{id}`, `GET /geo-zones/{id}/animals` (`?history=1`), `/visits`, `/devices`, `GET /geo-zones/locate?lat=&lon=` |

### Telemetri & lapangan
| Method | Path | Keterangan |
|---|---|---|
| POST | `/gps-readings` | ingest 1 titik; otomatis update geofence (masuk/keluar zona) + notifikasi zona rawan |
| POST | `/gps-readings/bulk` | `{readings: [...]}`; duplikat (device, waktu) dilewati |
| GET | `/gps-readings` | filter `device_id, animal_id, start_date, end_date, min_lat..max_lon, latest_only, limit` (default 2000 terbaru) |
| GET | `/gps-readings/{id}`, `/gps-readings/{id}/weather` | |
| GET/POST | `/animal-geo-zone-visits`, `PATCH /animal-geo-zone-visits/{id}/exit` | workflow kunjungan zona |
| GET/POST | `/incidents`, `GET/PUT /incidents/{id}`, `DELETE` (admin), `PATCH /incidents/{id}/sync-status` (admin) | |
| GET/POST | `/incidents/{id}/media` | unggah bukti; `analyze=1` untuk langsung dianalisis AI |
| GET | `/weather-data`, `/weather-data/{id}` | |
| POST | `/weather-data/ingest`, `/weather-data/sync` | ingest manual / tarik dari Open-Meteo |
| GET/PATCH/DELETE | `/notifications`, `/notifications/{id}`, `/notifications/{id}/read`, `/notifications/read-all`, `/notifications/unread-count` | hanya milik user aktif |

### AI
| Method | Path | Keterangan |
|---|---|---|
| POST | `/ai/upload` (= `/media-uploads`) | multipart `file` (gambar/video/audio) + opsional `report_id, device_id, zone_id, latitude, longitude, captured_at` → `{media, prediction}` |
| POST | `/devices/{id}/captures` | ingest dari kamera jebak / perekam audio, lokasi diambil dari titik pemasangan perangkat |
| GET | `/ai-predictions` | filter `processing_status, verification_status, analysis_type, threat_level, min_threat, physical_condition, behavior, sound_category, predicted_species_id, predicted_animal_id, device_id, zone_id, ...` |
| GET | `/ai-predictions/pending-verification` | diurutkan dari ancaman tertinggi |
| GET | `/ai-predictions/{id}` | termasuk `raw_response` (kandidat individu, fitur akustik) |
| POST/PATCH | `/ai-predictions/{id}/verify` | `{verification_status, verified_species_id, verified_animal_id?}` |
| POST | `/ai-predictions/{id}/retry` | analisis ulang (baris sama) |
| GET | `/media-uploads`, `/media-uploads/{id}`, `/media-uploads/{id}/file`, `/media-uploads/{id}/predictions` | file disajikan dari private storage |
| POST | `/media-uploads/{id}/predictions` | picu analisis untuk media yang sudah ada |
| GET | `/ai/threats` | deteksi ancaman (`?min_level=medium`) |
| GET | `/ai/summary-report` | ringkasan eksekutif + statistik + `ai_insights` |
| GET | `/ai/vocabulary` | label Indonesia untuk kondisi, perilaku, kategori suara, tingkat ancaman |
| POST | `/ai/detect-objects` | analisis cepat gambar tanpa disimpan |
| GET | `/ai/health` | status engine AI |

### Laporan & ekspor
`GET /aggregate-reports`, `POST /aggregate-reports/generate` (`report_type` daily/weekly/monthly, periode opsional),
`GET /aggregate-reports/{id}`, `GET /aggregate-reports/{id}/download` (CSV), `DELETE /aggregate-reports/{id}`.

Ekspor (CSV UTF-8 dengan BOM): `/export/gps/csv`, `/export/gps/geojson`, `/export/animals/csv`, `/export/species/csv`,
`/export/devices/csv`, `/export/incidents/csv`, `/export/weather/csv`, `/export/ai-predictions/csv`,
`/export/geo-zones/csv`, `/export/geo-zones/geojson`, `/export/zone-visits/csv`, `/export/aggregate-report/{id}`,
`/export/users/csv`, `/export/activity-logs/csv` (admin). Filter umum: `start_date`, `end_date`.

## Pipeline AI

```
kamera / perekam audio / unggahan petugas
  → simpan media (private storage) + lokasi (input atau titik pemasangan perangkat) + zona
  → analisis
       gambar : spesies, jumlah individu, kondisi fisik, perilaku, ciri pembeda, aktivitas manusia (senjata, jerat, penebangan, api)
       video  : frame diambil dengan ffmpeg lalu dianalisis seperti gambar
       audio  : kategori suara (satwa, tembakan, gergaji mesin, pohon tumbang, mesin, kendaraan, suara manusia, ...)
  → identifikasi individu: skor kandidat dari jarak GPS kalung saat waktu rekam, kehadiran di zona
    yang sama (animal_geo_zones), kecocokan ciri fisik (animals.distinctive_marks), dan apakah hanya ada satu individu spesies tsb.
  → tingkat ancaman → insiden otomatis (perburuan / perambahan / kebakaran_hutan / kesehatan_satwa)
    + notifikasi risk_zone ke petugas lapangan & admin
  → verifikasi petugas
```

Engine:
- Gambar & teks: Groq (`GROQ_VISION_MODEL`, `GROQ_TEXT_MODEL`).
- Audio: urutan prioritas: (1) microservice `AI_AUDIO_SERVICE_URL` (mis. YAMNet/BirdNET),
  kontrak `POST {url}/classify-audio` multipart `file` → `{label, confidence, category?, species?}`;
  (2) Groq Whisper + LLM dengan fitur akustik; (3) heuristik fitur akustik WAV (fallback offline, perlu verifikasi petugas).
- Bila engine tidak tersedia, upload tetap tersimpan dengan `processing_status = failed` dan bisa di-`retry`.
