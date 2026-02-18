#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="$ROOT_DIR/dist/infinityfree"
ZIP_PATH="$ROOT_DIR/dist/doitly-infinityfree-upload.zip"

rm -rf "$DIST_DIR"
mkdir -p "$DIST_DIR"

# Arquivos necessários para rodar o app no InfinityFree
cp -R "$ROOT_DIR/public" "$DIST_DIR/public"
cp -R "$ROOT_DIR/actions" "$DIST_DIR/actions"
cp -R "$ROOT_DIR/config" "$DIST_DIR/config"
cp -R "$ROOT_DIR/app" "$DIST_DIR/app"

# SQL já sanitizado para import em hospedagem compartilhada
mkdir -p "$DIST_DIR/sql"
cp "$ROOT_DIR/sql/doitly_infinityfree.sql" "$DIST_DIR/sql/doitly_infinityfree.sql"

# Entrada principal na raiz do htdocs
cat > "$DIST_DIR/index.php" <<'PHP'
<?php
header('Location: public/index.php');
exit;
PHP

mkdir -p "$ROOT_DIR/dist"
(
  cd "$DIST_DIR"
  zip -r "$ZIP_PATH" .
)

echo "Pacote gerado em: $ZIP_PATH"
echo "Extraia e envie o conteúdo para htdocs no InfinityFree."
