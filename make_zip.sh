#!/bin/bash

PLUGIN_ID="espeasyTCalmant"
ZIP_NAME="$PLUGIN_ID.zip"

rm -fr "$ZIP_NAME"
zip -r "$ZIP_NAME" "core" "desktop" "plugin_info" "resources" || exit $?

echo
echo "ZIP file.: $ZIP_NAME"
echo "Plugin ID: $PLUGIN_ID"
