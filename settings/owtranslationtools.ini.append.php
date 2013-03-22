<?php /* 

[Users]
# ID de l'Admin User
AdminID=14
AnonymousID=10

[Update]
# lors d'une mise-à-jour/ajoute d'une langue à un objet est que il faut definir la nouvelle langue comme principale ?
# valeurs possibles : 0/1, true/false, yes/no, disabled/enabled, on/off
do_update_initial_language=0

[Scripts]
# pour desactiver la pagination, valeurs possibles : false, no, 0, disabled, off
# valeur minimal 10 (si inferieur 10 sera pris en compte)
pagination=100

[Filters]
# filtres à prendre en consideration lors de l'éxécution des méthode de la class owTranslationTools
# les filtres seront utilisés dans les clauses WHERE des requete SQL
# pour les nom des champs vérifier la table concerné par la méthode
# les filtres sont toujours en group de 3; si on veut appliquer plus d'un filtre on les met à la suite
# et entre les filtre 'AND' sera appliqué
# si le total des elements d'un filtre n'est pas divisible par 3, aucun filtre ne sera appliqué
# exemple : 
# <nom_de_la_methode>[]=<nom_du_champ>
# <nom_de_la_methode>[]=<in/=/</etc..>
# <nom_de_la_methode>[]=<valeur>
#objectIDListByLangID[]=section_id
#objectIDListByLangID[]=in
#objectIDListByLangID[]=(1,2)

*/ ?>
