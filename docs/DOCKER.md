# Docker Setup Guide

This project supports both **development** and **production** Docker configurations.

---

## 🚀 Production Mode (Recommended for deployment)

**Features:**
- ✅ Automatic panel build (no manual npm commands needed)
- ✅ Multi-stage Docker build
- ✅ Optimized image size
- ✅ All dependencies bundled in the image

**Usage:**
```bash
# Build and start all services
docker-compose build
docker-compose up -d

# View logs
docker-compose logs -f app

# Stop services
docker-compose down
```

**What happens:**
1. Dockerfile builds the Vue.js panel automatically (stage 1)
2. PHP/Swoole server is built with all dependencies (stage 2)
3. Panel dist is copied into the final image
4. No volume mounts for source code

---

## 🛠️ Development Mode (Recommended for active development)

**Features:**
- ✅ Hot reload for both frontend and backend
- ✅ Live code changes without rebuilding
- ✅ Vite dev server for instant panel updates
- ✅ Source code mounted as volumes

**Usage:**
```bash
# Build and start development services
docker-compose -f docker-compose.dev.yml build
docker-compose -f docker-compose.dev.yml up -d

# View logs
docker-compose -f docker-compose.dev.yml logs -f app
docker-compose -f docker-compose.dev.yml logs -f panel-dev

# Stop services
docker-compose -f docker-compose.dev.yml down
```

**Access points:**
- Backend API: http://localhost:9501
- Frontend (Vite dev server with hot reload): http://localhost:3000

**What happens:**
1. PHP server runs with source code mounted (live reload)
2. Separate Vite dev server runs for the panel with hot module replacement
3. Changes to `server/` or `panel/src/` are reflected instantly
4. Panel dependencies are automatically installed on container startup
5. File watching uses polling for compatibility with Docker volumes on Windows/Mac

---

## 📁 File Structure

```
.
├── Dockerfile                  # Production multi-stage build
├── Dockerfile.dev              # Development build (PHP only)
├── docker-compose.yml          # Production configuration
├── docker-compose.dev.yml      # Development configuration
├── server/                     # PHP/Swoole backend
└── panel/                      # Vue.js frontend
```

---

## 🔄 Switching Between Modes

**From Dev to Prod:**
```bash
docker-compose -f docker-compose.dev.yml down
docker-compose build
docker-compose up -d
```

**From Prod to Dev:**
```bash
docker-compose down
docker-compose -f docker-compose.dev.yml up -d
```

---

## 🐛 Troubleshooting

**"vendor/autoload.php not found"**
- Solution: Rebuild the image: `docker-compose build --no-cache`

**"Cannot find module @rollup/rollup-linux-x64-musl"**
- This is resolved by using `node:20-slim` (Debian-based) instead of Alpine
- The dev configuration automatically handles this with proper volume mounts

**"panel dist is empty"**
- Production: Rebuild the image (panel builds automatically)
- Development: Container automatically installs dependencies on startup

**"Changes not reflecting in development"**
- Make sure you're accessing http://localhost:3000 for the panel (not 9501)
- The vite.config.js has `usePolling: true` enabled for Docker file watching
- Try hard refresh (Ctrl+F5) after container restart
- Check if volumes are mounted correctly

**"Changes not reflecting in production"**
- Production: You must rebuild the image for any code changes

---

## 📊 Comparison

| Feature      | Production            | Development          |
|--------------|-----------------------|----------------------|
| Build time   | Slower (builds panel) | Faster               |
| Code changes | Rebuild required      | Instant (hot reload) |
| Panel build  | Automatic             | Manual first time    |
| Image size   | Optimized             | Larger               |
| Best for     | Deployment, testing   | Active development   |

---

## 💡 Tips

1. **Always use development mode when coding** - saves time with hot reload
2. **Test in production mode before deploying** - catches build issues
3. **Use `.env` files** for environment-specific configs
4. **Volume mounts preserve data** between container restarts (databases, app-data)
