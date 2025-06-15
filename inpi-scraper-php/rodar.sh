#!/bin/bash
export PANTHER_NO_SANDBOX=1
export PANTHER_CHROME_BINARY=/usr/bin/google-chrome  # Caminho correto do Chrome

php raspar_patentes.php
