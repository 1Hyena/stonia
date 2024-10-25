<?php

$skills = json_decode(file_get_contents("skills.json"), true);
$guilds = json_decode(file_get_contents("guildmasters.json"), true);

foreach ($skills as $gsn => $skill) {
    if (!array_key_exists($gsn, $guilds)) {
        continue;
    }

    $guilds[$skill] = $guilds[$gsn];
    unset($guilds[$gsn]);
}

$headings = array(
    "Skill"   => ":---------------",
    "Teacher" => ":------------------------------",
    "Area"    => ":----------------------"
);

print("# All skills and spells ".str_repeat("#", 56)."\n\n");

foreach ($headings as $heading => $underline) {
    print(
        ($heading === "Skill" ? "| " : " | ").
        sprintf(
            "%".strlen($underline)."s", $heading
        )
    );
}

print(" |\n");

foreach ($headings as $heading => $underline) {
    print(($heading === "Skill" ? "| " : " | ").$underline);
}

print(" |\n");

$fmt1 = "%-".strlen($headings['Skill']).".".strlen($headings['Skill'])."s";
$fmt2 = "%-".strlen($headings['Teacher']).".".strlen($headings['Teacher'])."s";
$fmt3 = "%-".strlen($headings['Area']).".".strlen($headings['Area'])."s";

foreach ($guilds as $skill => $teachers) {
    foreach ($teachers as $teacher => $info) {
        $area = trim(substr($info['area'], 0, strpos($info['area'], "(")));
        print(
            "| ".sprintf($fmt1, $skill).
            " | ".sprintf($fmt2, $info['short_desc']).
            " | ".sprintf($fmt3, $area)." |\n"
        );
    }
}
