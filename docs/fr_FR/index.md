# Plugin Github

Plugin permettant de récupérer les informations de votre compte Github ainsi que celles de vos différents repositories.

Ce qui est disponible :
- Compte : nombre de followers
- Compte : Nombre de following
- Repository : nombre de watchers
- Repository : nombre de forks
- Repository : nombre de tickets ouverts
- Repository : nombre de pull-requests ouvertes

# Configuration

## Prérequis

Sur Github, vous devez générer un **personnal access token** et lui donner accès aux scopes suivants : repo (all), notifications, user (all). 

Plus d'informations [ici](https://docs.github.com/en/free-pro-team@latest/github/authenticating-to-github/creating-a-personal-access-token).

## Configuration du plugin

Sur la page de configuration du plugin, vous pouvez choisir :
- de récupérer ou non les repositories privés
- de récupérer ou non les forks
- la pièce par défaut pour chaque repository récupéré

## Configuration des équipements

Pour accéder aux différents équipements **Github**, dirigez-vous vers le menu **Plugins → Programmation → Github**.

Cliquez sur "Ajouter un compte Github"

Sur la page de l'équipement, renseignez votre login Github, ainsi que le token que vous avez généré (voir plus haut).

Cliquez ensuite sur "Scanner" pour récupérer vos repositories.

# Contributions

Ce plugin gratuit est ouvert à contributions (améliorations et/ou corrections). N'hésitez pas à soumettre vos pull-requests sur <a href="https://github.com/hugoKs3/plugin-github" target="_blank">Github</a>

# Credits

Ce plugin s'est inspiré des travaux suivants :

- [jmvedrine](https://github.com/jmvedrine) via son plugin Livebox : [plugin-livebox](https://github.com/jmvedrine/plugin-livebox)

# Disclaimer

-   Ce plugin ne prétend pas être exempt de bugs.
-   Ce plugin vous est fourni sans aucune garantie. Bien que peu probable, si il venait à corrompre votre installation Jeedom, l'auteur ne pourrait en être tenu pour responsable.

# ChangeLog
Disponible [ici](./changelog.html).
