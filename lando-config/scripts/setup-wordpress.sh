#!/bin/bash

POSITIONAL=()
set -- "${POSITIONAL[@]}" # restore positional parameters

if [[ $CI = true ]] ; then
	# are we in a CI environment?
	echo 'forcing non-interactive mode for CI environment'
	INTERACTIVE='NO'
else
	# not in a CI environment, default to interactive mode
	INTERACTIVE=${INTERACTIVE:-'YES'}
fi

# Install and configure WordPress if we haven't already
main() {
  BOLD=$(tput bold)
  NORMAL=$(tput sgr0)

  WP_DIR="$LANDO_MOUNT/wordpress"

  if ! [[ -d "$WP_DIR"/wp-content/plugins/timber-library ]] ; then
    echo 'Linking timber plugin directory...'
    ln -s "../../../" "$WP_DIR"/wp-content/plugins/timber-library
  fi

  if ! [[ -d "$WP_DIR"/wp-content/plugins/advanced-custom-fields ]] ; then
    echo 'Linking timber plugin directory...'
    ln -s "../../../wp-content/plugins/advanced-custom-fields/" "$WP_DIR"/wp-content/plugins/advanced-custom-fields
  fi

  if ! [[ -d "$WP_DIR"/wp-content/plugins/co-authors-plus ]] ; then
    echo 'Linking timber plugin directory...'
    ln -s "../../../wp-content/plugins/co-authors-plus" "$WP_DIR"/wp-content/plugins/co-authors-plus
  fi

  echo 'Checking for WordPress config...'
  if wp_configured ; then
    echo 'WordPress is configured'
  else
    read -d '' extra_php <<'EOF'
// log all notices, warnings, etc.
error_reporting(E_ALL);

// enable debug logging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
EOF

    # create a wp-config.php
    wp config create \
      --dbname="wordpress" \
      --dbuser="wordpress" \
      --dbpass="wordpress" \
      --dbhost="database" \
      --path="$WP_DIR" \
      --extra-php < <(echo "$extra_php")
  fi

  echo 'Checking for WordPress installation...'
  if wp_installed ; then
    echo 'WordPress is installed'
  else
    if [[ $INTERACTIVE = 'YES' ]] ; then

      #
      # Normal/default interactive mode: prompt the user for WP settings
      #

      read -p "${BOLD}Site URL${NORMAL} (https://timber.lndo.site): " URL
      URL=${URL:-'https://timber.lndo.site'}

      read -p "${BOLD}Site Title${NORMAL} (Timber): " TITLE
      TITLE=${TITLE:-'Timber'}

      # Determine the default username/email to suggest based on git config
      DEFAULT_EMAIL=$(git config --global user.email)
      DEFAULT_EMAIL=${DEFAULT_EMAIL:-'admin@example.com'}
      DEFAULT_USERNAME=$(echo $DEFAULT_EMAIL | sed 's/@.*$//')

      read -p "${BOLD}Admin username${NORMAL} ($DEFAULT_USERNAME): " ADMIN_USER
      ADMIN_USER=${ADMIN_USER:-"$DEFAULT_USERNAME"}

      read -p "${BOLD}Admin password${NORMAL} (timber): " ADMIN_PASSWORD
      ADMIN_PASSWORD=${ADMIN_PASSWORD:-'timber'}

      read -p "${BOLD}Admin email${NORMAL} ($DEFAULT_EMAIL): " ADMIN_EMAIL
      ADMIN_EMAIL=${ADMIN_EMAIL:-"$DEFAULT_EMAIL"}

    else

      #
      # NON-INTERACTIVE MODE
      # ONE DOES NOT SIMPLY PROMPT FOR USER PREFERENCES
      #

      URL='http://timber.lndo.site'
      TITLE='Timber'
      ADMIN_USER='timber'
      ADMIN_PASSWORD='timber'
      ADMIN_EMAIL='timber+travisci@sitecrafting.com'

    fi

    # install WordPress
    wp core install \
      --url="$URL" \
      --title="$TITLE" \
      --admin_user="$ADMIN_USER" \
      --admin_password="$ADMIN_PASSWORD" \
      --admin_email="$ADMIN_EMAIL" \
      --skip-email \
      --path="$WP_DIR"
  fi

  # configure plugins and theme
  uninstall_plugins hello akismet
  wp --quiet --path="$WP_DIR" plugin activate advanced-custom-fields
  wp --quiet --path="$WP_DIR" plugin activate co-authors-plus

  # currently the timber plugin will not activate since incorrect code structure
  # wp --quiet --path="$WP_DIR" plugin activate timber-library

  # uninstall stock themes
  wp --quiet theme uninstall \
    twentyten \
    twentyeleven \
    twentytwelve \
    twentythirteen \
    twentyfourteen \
    twentyfifteen \
    twentysixteen \
    twentyseventeen \
    --path="$WP_DIR"

  wp option set permalink_structure '/%postname%/' --path="$WP_DIR"
  wp rewrite flush --path="$WP_DIR"

}


# Detect whether WP has been configured already
wp_configured() {
  [[ $(wp config path --path="$WP_DIR" 2>/dev/null) ]] && return
  false
}

# Detect whether WP is installed
wp_installed() {
  wp --quiet core is-installed --path="$WP_DIR"
  [[ $? = '0' ]] && return
  false
}

uninstall_plugins() {
  for plugin in $@ ; do
    wp --path="$WP_DIR" plugin is-installed $plugin  2>/dev/null
    if [[ "$?" = "0" ]] ; then
      wp --path="$WP_DIR" plugin uninstall $plugin
    fi
  done
}


main
