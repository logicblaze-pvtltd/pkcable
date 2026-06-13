<?php
function getInitials($name) {
    // Name ke aaspas se faltu spaces khatam karne k liye
    $name = trim($name);
    if (empty($name)) return '';

    // Naam ko spaces ki bunyad par alag alag words me torna
    $words = preg_split('/\s+/', $name);
    $initials = '';

    foreach ($words as $word) {
        // Har word ka pehla letter uthana aur capital karna
        $initials .= strtoupper(substr($word, 0, 1));
    }

    return $initials;
}
?>