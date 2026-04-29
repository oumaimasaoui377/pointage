# 🏫 Baudimont Time Flow — Guide d'installation XAMPP

## Prérequis
- [XAMPP](https://www.apachefriends.org/) installé (Apache + MySQL + PHP ≥ 8.0)
- [Node.js](https://nodejs.org/) ≥ 18 + npm

---

## 📦 Étape 1 — Importer la base de données

1. Démarrez **Apache** et **MySQL** dans le panneau XAMPP
2. Ouvrez **phpMyAdmin** → [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
3. Cliquez sur l'onglet **"Importer"** (barre du haut)
4. Cliquez **"Choisir un fichier"** → sélectionnez `database/baudimont_timeflow.sql`
5. Cliquez **"Exécuter"**

✅ La base `baudimont_timeflow` est créée avec tables + données de démo.

### Comptes de démo
| Nom | Email | Mot de passe | Rôle |
|-----|-------|-------------|------|
| Marie Dupont | marie@baudimont.fr | demo1234 | Membre DSI |
| Pierre Martin | pierre@baudimont.fr | demo1234 | Membre DSI |
| Sophie Laurent | sophie@baudimont.fr | demo1234 | Membre Tech |
| Karim Belkacem | karim@baudimont.fr | demo1234 | Membre Restauration |
| Aïcha Ndiaye | aicha@baudimont.fr | demo1234 | Membre Entretien |
| Admin DSI | admin@baudimont.fr | demo1234 | **Admin** DSI |

---

## 🔌 Étape 2 — Déployer l'API PHP

Copiez le dossier `api/` dans votre dossier XAMPP :

- **Windows** : `C:\xampp\htdocs\baudimont-api\`
- **Mac/Linux** : `/opt/lampp/htdocs/baudimont-api/`

Le dossier final doit ressembler à :
```
htdocs/
└── baudimont-api/
    ├── api/
    │   ├── config.php
    │   ├── users.php
    │   └── punches.php
    └── (pas d'index nécessaire)
```

> 💡 Si vous avez changé le mot de passe MySQL dans XAMPP, modifiez `DB_PASS` dans `api/config.php`.

---

## ⚛️ Étape 3 — Lancer le front-end React

Ouvrez un terminal dans le dossier du projet :

```bash
npm install
npm run dev
```

L'application s'ouvre sur → [http://localhost:8080](http://localhost:8080)

---

## 🔗 Architecture

```
Navigateur (React :8080)
        │
        │  fetch()
        ▼
Apache XAMPP (:80)
  └── /baudimont-api/api/
        ├── users.php     ← gestion utilisateurs + login
        └── punches.php   ← gestion des pointages
              │
              │  PDO
              ▼
        MySQL XAMPP
          └── baudimont_timeflow
                ├── users
                ├── punch_records
                ├── team_codes
                └── v_today_punches (vue)
```

---

## ⚙️ Points importants

- **CORS** : L'API autorise les requêtes depuis `http://localhost:8080` (port Vite par défaut). Si vous changez le port, mettez à jour `api/config.php`.
- **Mots de passe** : Les mots de passe sont hashés en **bcrypt** côté PHP. Ne jamais stocker de mots de passe en clair en production.
- **Logo** : Le logo `baudimont-logo.png` est déjà intégré dans `src/assets/` et utilisé dans l'en-tête.
- **localStorage** : Le front utilise encore `localStorage` pour la session tant que vous n'avez pas branché l'API PHP. Pour basculer vers MySQL, remplacez les appels dans `src/lib/store.ts` par des `fetch()` vers `api/users.php`.

---

## 🆘 Dépannage

| Problème | Solution |
|----------|----------|
| `CORS error` | Vérifiez que Apache tourne et que `api/config.php` a le bon port |
| `Connexion DB échouée` | Vérifiez que MySQL tourne dans XAMPP |
| `Table doesn't exist` | Ré-importez `database/baudimont_timeflow.sql` |
| Port 8080 occupé | Changez le port dans `vite.config.ts` → `port: 3000` |
