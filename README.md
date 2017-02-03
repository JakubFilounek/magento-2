# Magento 2.x plugin pro propojení s Ecomail.cz

Aktuální verzi pluginu lze stáhnout zde:

https://github.com/Ecomailcz/magento-2/releases/download/1.0.0/Ecomail_Ecomail-magento2-1.0.0.zip

# Instalace modulu

1. Překopírovat obsah archivu do kořenové složky
2. příkaz. řádka: php bin/magento module:enable Ecomail_Ecomail
3. příkaz. řádka: php bin/magento setup:upgrade
4. příkaz. řádka: php bin/magento setup:static-content:deploy
5. příkaz. řádka: php bin/magento cache:clean
