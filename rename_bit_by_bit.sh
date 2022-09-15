#!/bin/bash


# recursiver glob ** aktivieren 
shopt -s globstar

# nur ausgabe, ohne echtes umbennen:
#rename -n 's/lti/orcalti/g' **/*

# lti in dateinamen umbenennen
rename 's/lti/orcalti/g' **/*

# token, die den gerade umbenannten dateinamen entsprechen, in allen dateien umbenennen
find ./ -type f -exec sed -i -e "s/orcalti_stepslib/orcalti_stepslib/g" {} \;
find ./ -type f -exec sed -i -e "s/orcalti_activity/orcalti_activity/g" {} \;
find ./ -type f -exec sed -i -e "s/orcaltisource/orcaltisource/g" {} \;
find ./ -type f -exec sed -i -e "s/spservice/spservice/g" {} \;
find ./ -type f -exec sed -i -e "s/basic_orcalti_link/basic_orcalti_link/g" {} \;

# bezeichnungen von funktionen in denen lti vorkommt, in allen dateien umbennen
token_list=$(grep -oh -E "function\ [[:alpha:]_]*lti[[:alpha:]_]*"  -R * | sort -u)
for token in $token_list
do
    new_token=${token/lti/orcalti}
    find ./ -type f -exec sed -i -e "s/$token/$new_token/g" {} \;
done

# mod_orcalti und mod/orcalti und mod\orcalti und mod-orcalti in allen dateien umbennen
find ./ -type f -exec sed -i -e "s/mod_orcalti/mod_orcalti/g" {} \;
find ./ -type f -exec sed -i -e "s/mod\/lti/mod\/orcalti/g" {} \;
find ./ -type f -exec sed -i -e "s/mod\\\lti/mod\\\orcalti/g" {} \;
find ./ -type f -exec sed -i -e "s/mod-orcalti/mod-orcalti/g" {} \;

# "orcalti" und 'orcalti' in allen dateien umbenennen
find ./ -type f -exec sed -i -e "s/\"lti\"/\"orcalti\"/g" {} \;
find ./ -type f -exec sed -i -e "s/'orcalti'/'orcalti'/g" {} \;

# umbenennen der datenbanktabellen und verweise in allen dateien
#grep -oh -E "TABLE.*lti[[:alpha:]_]*"  -R * | sort -u
find ./ -type f -exec sed -i -e "s/orcalti_tool_proxies/orcalti_tool_proxies/g" {} \;
find ./ -type f -exec sed -i -e "s/orcalti_submission/orcalti_submission/g" {} \;
find ./ -type f -exec sed -i -e "s/orcalti_tool_settings/orcalti_tool_settings/g" {} \;
find ./ -type f -exec sed -i -e "s/orcalti_types/orcalti_types/g" {} \;

# umbenennen der datenbankfelder und verweise in allen dateien
#grep -oh -E "FIELD.*lti[[:alpha:]_]*"  -R * | sort -u
find ./ -type f -exec sed -i -e "s/orcaltiid/orcaltiid/g" {} \;
find ./ -type f -exec sed -i -e "s/orcaltilinkid/orcaltilinkid/g" {} \;

# spservice_gradebookservices ist zu lang, daher Umbenennung
rename 's/spservice/spservice/g' **/*
find ./ -type f -exec sed -i -e "s/spservice/spservice/g" {} \;

# ACHTUNG Datenbank default wert basiclti !!


# definition von übergreifenden funktionen löschen (sind bereits über das normale lti-plugin vorhaben)
# 3261 get_tag()
sed -i '3252,3271d' locallib.php 

# 3019 serialise_tool_proxy()
# 2986 serialise_tool_type()
# 2973 get_tool_type_instance_ids()
# 2933 get_tool_type_capability_groups()
# 2893 get_tool_type_state_info()
# 2874 get_tool_proxy_urls()
# 2852 get_tool_type_urls()
# 2837 get_tool_type_course_url()
# 2824 get_tool_proxy_edit_url()
# 2811 get_tool_type_edit_url()
# 2788 get_tool_type_icon_url()
sed -i '2781,3038d' locallib.php 


# fix for error: Duplicate admin category name: modorcaltifolder
find ./ -type f -exec sed -i -e "s/modorcaltifolder/modorcaltifolder/g" {} \;

# vermutlich sollte auch modorcalti umbenannt werden
find ./ -type f -exec sed -i -e "s/modorcalti/modorcalti/g" {} \;

# fix for errors like this:  Constant ORCALTI_LAUNCH_CONTAINER_DEFAULT already defined
# ACHTUNG vielleicht lieber zeilen löschen???
find ./ -type f -exec sed -i -e "s/ORCALTI_/ORCALTI_/g" {} \;
