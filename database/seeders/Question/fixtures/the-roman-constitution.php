<?php

declare(strict_types=1);

/**
 * Question fixtures for the quiz expanded from "Magistrates, Assemblies and the Checks
 * Between Them", the accepted proposal for the source "the-roman-constitution.pdf".
 *
 * The sections follow that proposal's outline in order, eight questions each. Every
 * question offers four options, of which the one at `correct` is the answer; the other
 * three are meant to be genuinely tempting to someone who has read the material once.
 *
 * @return array<int, array{text: string, correct: int, options: array<int, string>, explanation: string}>
 */
return [

    // From kings to consuls.

    [
        'text' => 'In which year does Roman tradition place the expulsion of the last king and the founding of the Republic?',
        'correct' => 1,
        'options' => [
            '753 BC',
            '509 BC',
            '451 BC',
            '390 BC',
        ],
        'explanation' => 'Tradition dates the expulsion of Tarquinius Superbus, and with it the first consulship, to 509 BC. The other three years are real landmarks that sit either side of it: 753 BC is the traditional founding of the city, 451 BC the year of the first decemvirate that produced the Twelve Tables, and 390 BC the Gallic sack.',
    ],
    [
        'text' => 'Who was the seventh and last king of Rome?',
        'correct' => 0,
        'options' => [
            'Lucius Tarquinius Superbus',
            'Servius Tullius',
            'Ancus Marcius',
            'Numa Pompilius',
        ],
        'explanation' => 'Tarquinius Superbus was the last of the seven kings, and his expulsion is the event the Republic dated itself from. Servius Tullius was his immediate predecessor and the king credited with the census, Ancus Marcius the fourth king, and Numa Pompilius the second.',
    ],
    [
        'text' => 'Which feature of the monarchy did the consulship deliberately break with?',
        'correct' => 2,
        'options' => [
            'The right to take the auspices before public business',
            'The escort of lictors carrying the fasces',
            'The holding of supreme command by one man for life',
            'The power to command the army in the field',
        ],
        'explanation' => 'The consulship kept the substance of royal power and divided it: two holders instead of one, and one year instead of a lifetime. The auspices, the lictors and the command of the army all passed to the consuls intact, which is why the three other options survived the change unaltered.',
    ],
    [
        'text' => 'What is meant by the collegiality of Roman magistracies?',
        'correct' => 3,
        'options' => [
            'That magistrates were drawn from the same college of priests',
            'That a magistrate answered to the Senate as a body rather than to any individual',
            'That candidates stood for office as a slate rather than singly',
            'That every office was held by at least two men of equal power',
        ],
        'explanation' => 'Collegiality means an office was never held alone: two consuls, two censors, several praetors and tribunes, each with the same powers as the others. Because a colleague of equal rank could block an act, the rule did most of the practical work of restraining any one magistrate.',
    ],
    [
        'text' => 'What did annuality mean in the constitution of the Republic?',
        'correct' => 0,
        'options' => [
            'That an office was held for a single year',
            'That an office could be held only once in a lifetime',
            'That elections were held in the same month every year',
            'That a magistrate reported to the Senate once a year',
        ],
        'explanation' => 'Annuality fixed the term of office at one year, so that power was surrendered on a known date rather than held indefinitely. Repetition was not forbidden by the principle itself; the intervals between repeated offices were imposed separately, and by law only from 180 BC.',
    ],
    [
        'text' => 'Which office preserved the title of king after the monarchy had been abolished?',
        'correct' => 2,
        'options' => [
            'The pontifex maximus',
            'The flamen Dialis',
            'The rex sacrorum',
            'The augur',
        ],
        'explanation' => 'The rex sacrorum, the "king of sacred things", inherited the religious duties the kings had performed, and was barred from holding political office so that the title could never again carry power. The pontifex maximus, the flamen Dialis and the augurs were all priesthoods, but none of them carried the royal name.',
    ],
    [
        'text' => 'What were the fasces carried before a Roman magistrate?',
        'correct' => 1,
        'options' => [
            'The ivory chair on which he sat to give judgement',
            'A bundle of rods, symbolising his power to compel and to punish',
            'The purple-bordered toga worn by curule magistrates',
            'The register of citizens compiled every five years',
        ],
        'explanation' => 'The fasces were a bundle of rods, carried by lictors walking ahead of a magistrate who held imperium, and outside the city an axe was bound into the bundle to signify the power of life and death. The other three answers name real objects of Roman public life: the sella curulis, the toga praetexta and the census.',
    ],
    [
        'text' => 'Which pair does tradition name as the first consuls of the Republic?',
        'correct' => 3,
        'options' => [
            'Publius Valerius Publicola and Marcus Horatius Pulvillus',
            'Menenius Agrippa and Appius Claudius',
            'Marcus Furius Camillus and Titus Manlius',
            'Lucius Junius Brutus and Lucius Tarquinius Collatinus',
        ],
        'explanation' => 'Brutus, who led the rising against the king, and Collatinus, the husband of Lucretia, are named as the first pair. Valerius Publicola is the strongest of the wrong answers because he really did hold the consulship in the same year, replacing Collatinus when the latter resigned on account of his family name.',
    ],

    // The cursus honorum.

    [
        'text' => 'Which magistracy stood at the bottom of the cursus honorum?',
        'correct' => 2,
        'options' => [
            'The aedileship',
            'The tribunate of the plebs',
            'The quaestorship',
            'The praetorship',
        ],
        'explanation' => 'The quaestorship was the first regular step, and from Sulla onwards holding it carried automatic entry to the Senate. The aedileship and the praetorship came later on the ladder, and the tribunate of the plebs stood outside the sequence altogether, since patricians could not hold it.',
    ],
    [
        'text' => 'What was the chief responsibility of a quaestor?',
        'correct' => 0,
        'options' => [
            'The custody and accounting of public money',
            'The upkeep of temples and the staging of the public games',
            'The presidency of the standing criminal courts',
            'The registration of citizens into their property classes',
        ],
        'explanation' => 'Quaestors handled the treasury at Rome and the military chest of a commander in the field, which made the office a useful apprenticeship in public administration. The three wrong answers describe the aediles, the praetors and the censors respectively.',
    ],
    [
        'text' => 'Which magistrates were responsible for the grain supply, the public games and the upkeep of streets and public buildings?',
        'correct' => 1,
        'options' => [
            'The quaestors',
            'The aediles',
            'The censors',
            'The tribunes of the plebs',
        ],
        'explanation' => 'The aedileship carried charge of the city itself, and above all of the games. Because a candidate paid for much of that display out of his own pocket, the office was an expensive but effective way of buying the recognition needed to win the praetorship.',
    ],
    [
        'text' => 'What was the principal duty of the praetor urbanus?',
        'correct' => 3,
        'options' => [
            'Commanding the armies stationed in Italy',
            'Supervising the treasury in the absence of the consuls',
            'Presiding over the plebeian assembly',
            'Administering justice between citizens at Rome',
        ],
        'explanation' => 'The urban praetor was the chief judicial magistrate at Rome, and the edict he issued on taking office was one of the main engines by which Roman private law developed. Praetors did hold imperium and could command armies, but that was not the defining business of the urban praetorship.',
    ],
    [
        'text' => 'Which law of 180 BC first fixed minimum ages and compulsory intervals for the offices of the cursus honorum?',
        'correct' => 1,
        'options' => [
            'The lex Hortensia',
            'The lex Villia annalis',
            'The lex Canuleia',
            'The lex Ogulnia',
        ],
        'explanation' => 'The lex Villia annalis turned what had been custom into law, setting a minimum age for each office and a required gap between them. The other three laws are real and important, but they concern the force of plebiscites, intermarriage between the orders, and plebeian admission to the priesthoods.',
    ],
    [
        'text' => 'Which office was held for eighteen months, but only once every five years?',
        'correct' => 0,
        'options' => [
            'The censorship',
            'The dictatorship',
            'The consulship',
            'The praetorship',
        ],
        'explanation' => 'Censors were elected at five-year intervals and held office for eighteen months, long enough to complete the census and the revision of the Senate roll but not long enough to make the post a standing power. The dictatorship was the office with the six-month limit, and the consulship and praetorship were annual.',
    ],
    [
        'text' => 'Which of these duties belonged to the censors?',
        'correct' => 2,
        'options' => [
            'Presiding over the standing courts',
            'Commanding armies in the provinces',
            'Compiling the citizen register and revising the roll of the Senate',
            'Supervising the annual games in honour of Jupiter',
        ],
        'explanation' => 'The censors counted the citizens, assigned them to their property classes and tribes, and struck from or added to the list of senators. That last power gave a pair of censors a moral authority out of all proportion to the eighteen months they served.',
    ],
    [
        'text' => 'What was a promagistrate?',
        'correct' => 1,
        'options' => [
            'A magistrate elected to finish the term of one who had died in office',
            'A former magistrate whose command was extended beyond his year of office',
            'An official appointed by the Senate without any election',
            'A magistrate serving the first of two permitted terms',
        ],
        'explanation' => 'Prorogation kept a man in command of an army or a province as proconsul or propraetor after his year in office had ended, which was how Rome managed distant wars without breaking annuality outright. The first option describes a suffect magistrate, which is a different device for a different problem.',
    ],

    // The Senate.

    [
        'text' => 'How did a man normally come to sit in the Senate in the later Republic?',
        'correct' => 3,
        'options' => [
            'By inheriting his father\'s place on the roll',
            'By direct election to the Senate by the centuriate assembly',
            'By appointment by the consuls for a single year',
            'By holding an elected magistracy, beginning with the quaestorship',
        ],
        'explanation' => 'The Senate was not an elected chamber in its own right: it was the standing body of men who had already held office, enrolled by the censors and, after Sulla, admitted automatically on holding the quaestorship. Membership was for life, but it was earned by election to a magistracy rather than inherited.',
    ],
    [
        'text' => 'Which magistrates drew up the roll of the Senate in the developed Republic?',
        'correct' => 0,
        'options' => [
            'The censors',
            'The consuls',
            'The tribunes of the plebs',
            'The pontifex maximus and the augurs',
        ],
        'explanation' => 'From the lex Ovinia onwards the censors enrolled senators and could remove a man for misconduct. Before that law the consuls had drawn up the list, which is what makes the second option a reasonable guess rather than an obviously wrong one.',
    ],
    [
        'text' => 'What was a senatus consultum?',
        'correct' => 2,
        'options' => [
            'A measure passed by the plebeian assembly',
            'The edict a praetor published on entering office',
            'A resolution of the Senate, formally advice rather than law',
            'A decree issued by a dictator during an emergency',
        ],
        'explanation' => 'A senatus consultum was, in strict constitutional terms, advice given to a magistrate: only an assembly could make law. In practice a magistrate who ignored the Senate faced the collective weight of every man who had held office before him, so the advice was very rarely refused.',
    ],
    [
        'text' => 'Why did the Senate\'s advice carry such weight, given that it could not legislate?',
        'correct' => 1,
        'options' => [
            'It could veto any measure put before the assemblies',
            'It was a permanent body of ex-magistrates, while magistrates changed every year',
            'Its members were elected directly by the whole people',
            'It alone had the right to command the armies',
        ],
        'explanation' => 'Continuity was the source of the Senate\'s authority. Consuls served a year and then joined it; the Senate was still there when they had gone, holding the accumulated experience of everyone who had governed before. It held no veto and no command of its own, which is exactly why its influence rested on standing rather than on formal power.',
    ],
    [
        'text' => 'Which area of public business did the Senate control most firmly in practice?',
        'correct' => 3,
        'options' => [
            'The trial of capital charges against citizens',
            'The election of the annual magistrates',
            'The passing of laws binding on the whole people',
            'Public finance, and the allocation of provinces and commands',
        ],
        'explanation' => 'The Senate decided what the treasury paid for and which magistrate received which province and army, and those two levers gave it a grip on policy that no formal power granted it. Elections and legislation belonged to the assemblies, and capital trials to the people or, later, the standing courts.',
    ],
    [
        'text' => 'What was the senatus consultum ultimum?',
        'correct' => 0,
        'options' => [
            'A resolution calling on the magistrates to see that the state took no harm',
            'A formal declaration of war on a foreign power',
            'The Senate\'s grant of a triumph to a victorious commander',
            'The appointment of a dictator for a term of six months',
        ],
        'explanation' => 'The so-called final decree urged the consuls to take whatever measures were needed to protect the state, and was first used against Gaius Gracchus in 121 BC. It created no new legal power, which is why its use remained bitterly contested: it appeared to license the killing of citizens who still, in law, held the right of appeal.',
    ],
    [
        'text' => 'What distinguished the princeps senatus?',
        'correct' => 2,
        'options' => [
            'He presided over meetings in place of the consuls',
            'He was the oldest senator present at a given sitting',
            'He stood first on the roll and was asked his opinion first in debate',
            'He was the senator who had formally proposed the motion under debate',
        ],
        'explanation' => 'The censors named one senator to head the roll, and he was called on first whenever opinions were taken. Speaking first in a body that voted by moving to the side of a speaker one agreed with was a real advantage, not merely an honour.',
    ],
    [
        'text' => 'Roughly how many members did the Senate have before Sulla enlarged it?',
        'correct' => 1,
        'options' => [
            'About one hundred',
            'About three hundred',
            'About six hundred',
            'About a thousand',
        ],
        'explanation' => 'The traditional figure is three hundred, which is why the increases that followed were so conspicuous: Sulla roughly doubled the body to six hundred, and Caesar pushed it towards nine hundred. The wrong answers are all real sizes the Senate held at some point, but not before Sulla.',
    ],

    // The assemblies.

    [
        'text' => 'Which assembly elected the consuls and the praetors?',
        'correct' => 3,
        'options' => [
            'The concilium plebis',
            'The comitia curiata',
            'The comitia tributa',
            'The comitia centuriata',
        ],
        'explanation' => 'The centuriate assembly elected the magistrates who held imperium, and it alone could declare an offensive war. The tribal assembly elected the lesser magistrates, the plebeian assembly elected the tribunes and aediles of the plebs, and the curiate assembly had shrunk to formalities by the later Republic.',
    ],
    [
        'text' => 'On what basis were voters distributed among the units of the centuriate assembly?',
        'correct' => 0,
        'options' => [
            'By property class, into voting units called centuries',
            'By the tribe in which they were registered',
            'By birth into the patrician or the plebeian order',
            'By age, with the youngest voting first',
        ],
        'explanation' => 'The centuriate assembly grew out of the army and kept the army\'s structure: citizens were sorted by the property they could be assessed on, and each class held a fixed number of centuries. Age divided each class into juniors and seniors, but it did not decide voting order between classes.',
    ],
    [
        'text' => 'Why did the centuriate assembly favour the wealthy?',
        'correct' => 2,
        'options' => [
            'Only landowners were admitted to it',
            'Each man\'s vote was weighted by the tax he paid',
            'The wealthiest centuries were both the most numerous and the first to vote',
            'The censors could strike poor citizens from the roll before an election',
        ],
        'explanation' => 'Voting was by unit and stopped as soon as a majority of units had been reached. The first class and the equestrian centuries together held a near-majority of the 193 centuries and voted at the head of the sequence, so on a matter where the rich agreed, the lower classes were often never reached at all.',
    ],
    [
        'text' => 'Which assembly was open to plebeians only?',
        'correct' => 1,
        'options' => [
            'The comitia tributa',
            'The concilium plebis',
            'The comitia centuriata',
            'The comitia curiata',
        ],
        'explanation' => 'The concilium plebis excluded patricians by definition, which is why it could elect the tribunes whose whole purpose was to protect the plebs. The comitia tributa is the easiest answer to confuse it with, since it also voted by tribe, but it included the whole citizen body.',
    ],
    [
        'text' => 'What was a plebiscite?',
        'correct' => 3,
        'options' => [
            'A resolution of the Senate addressed to the plebeian assembly',
            'A vote of the whole people on a proposal put by a consul',
            'A judgement of the tribunes on an appeal by a citizen',
            'A measure passed by the plebeian assembly on a tribune\'s motion',
        ],
        'explanation' => 'A plebiscitum was what the plebs enacted for itself. It began as binding only on plebeians, and the whole significance of the constitutional history that follows is that it ended as binding on everyone, which made the tribunes the most effective legislators in the state.',
    ],
    [
        'text' => 'Which law of 287 BC made the measures of the plebeian assembly binding on the whole people?',
        'correct' => 0,
        'options' => [
            'The lex Hortensia',
            'The lex Canuleia',
            'The lex Ogulnia',
            'The Twelve Tables',
        ],
        'explanation' => 'The lex Hortensia, carried after the last secession of the plebs, gave plebiscites the force of law over patricians as well. The other three are genuine milestones of the same long quarrel: intermarriage between the orders in 445, plebeian admission to the major priesthoods in 300, and the publication of the law itself around 450.',
    ],
    [
        'text' => 'Who presided over the concilium plebis?',
        'correct' => 1,
        'options' => [
            'A consul',
            'A tribune of the plebs',
            'A praetor',
            'The pontifex maximus',
        ],
        'explanation' => 'Only a tribune, or a plebeian aedile, could summon and put a motion to the plebeian assembly, since patrician magistrates had no standing in a body the patricians were excluded from. Consuls and praetors presided over the assemblies of the whole people instead.',
    ],
    [
        'text' => 'What could a Roman assembly do with a proposal that was put to it?',
        'correct' => 2,
        'options' => [
            'Amend it clause by clause and then vote on the amended text',
            'Send it back to the Senate to be redrafted',
            'Accept or reject it exactly as it stood',
            'Debate it and postpone the decision to a later meeting',
        ],
        'explanation' => 'A voting assembly was not a debating chamber. Argument happened beforehand at an informal meeting called a contio, which the presiding magistrate controlled; when the assembly itself was called, the only question before it was yes or no to the text as read out.',
    ],

    // Checks and emergency powers.

    [
        'text' => 'What was intercessio?',
        'correct' => 0,
        'options' => [
            'The power of a magistrate to block the act of an equal or lesser colleague',
            'The right of a citizen to appeal a capital sentence to the people',
            'The extension of a command beyond its year of office',
            'The taking of the auspices before public business began',
        ],
        'explanation' => 'Intercessio is the veto, and it worked downwards and sideways: one consul could stop the other, and a higher magistrate could stop a lower one. The other three options name provocatio, prorogation and the auspices, each of which is a real institution but a different one.',
    ],
    [
        'text' => 'Whose veto could halt the act of any magistrate except a dictator?',
        'correct' => 2,
        'options' => [
            'The censor\'s',
            'The princeps senatus\'',
            'The tribune of the plebs\'',
            'The pontifex maximus\'',
        ],
        'explanation' => 'The tribunes could interpose against any magistrate, against a decree of the Senate, and against a levy, which made a single tribune capable of bringing public business to a halt. The dictatorship was the deliberate exception, since an office created for emergencies would have been useless if it could be vetoed.',
    ],
    [
        'text' => 'What right did provocatio give a Roman citizen?',
        'correct' => 1,
        'options' => [
            'To stand for office once he had completed his military service',
            'To appeal to the people against a magistrate\'s capital punishment',
            'To summon an assembly to hear a grievance against a magistrate',
            'To refuse enrolment in a levy raised outside Italy',
        ],
        'explanation' => 'Provocatio put the ultimate power over a citizen\'s life with the assembly rather than with the magistrate holding the rods and the axe. It is the reason the final decree of the Senate was so contentious, since executions carried out under that decree were exactly what provocatio was supposed to prevent.',
    ],
    [
        'text' => 'What made the person of a tribune of the plebs sacrosanct?',
        'correct' => 3,
        'options' => [
            'A clause of the Twelve Tables',
            'A priesthood attached to the office by the pontiffs',
            'A law of the centuriate assembly granting him immunity',
            'A collective oath of the plebs to kill anyone who harmed him',
        ],
        'explanation' => 'Sacrosanctity rested on an oath sworn by the plebs, not on a statute: whoever laid hands on a tribune was declared forfeit and could be killed without trial. The protection was therefore only as strong as the plebs\' willingness to enforce it, which is precisely what failed in 133 and 121 BC.',
    ],
    [
        'text' => 'For how long could a dictator normally hold office?',
        'correct' => 1,
        'options' => [
            'Until the emergency that prompted his appointment had passed',
            'Six months',
            'One year, like the ordinary magistrates',
            'Eighteen months, as the censors did',
        ],
        'explanation' => 'The six-month limit, together with the expectation that a dictator resigned as soon as his task was done, is what kept the office compatible with the Republic. Sulla\'s dictatorship broke precisely this rule, which is why contemporaries treated it as something new rather than as a revival.',
    ],
    [
        'text' => 'Who appointed a dictator?',
        'correct' => 2,
        'options' => [
            'The centuriate assembly, by election',
            'The Senate, by decree',
            'A consul, acting on the Senate\'s advice',
            'The tribunes of the plebs, acting together',
        ],
        'explanation' => 'A dictator was named by a consul, normally after the Senate had resolved that one was needed. The distinction matters: the Senate could not create the office directly, and the people did not elect to it, so the emergency power still passed through the hands of a serving magistrate.',
    ],
    [
        'text' => 'What was the magister equitum?',
        'correct' => 0,
        'options' => [
            'The deputy a dictator named on taking office',
            'The senior of the two consuls for a given month',
            'The officer who conducted the review of the equestrian order',
            'The magistrate who presided over disputes among the equestrians',
        ],
        'explanation' => 'The master of the horse was appointed by the dictator himself and held office only as long as the dictator did, so the emergency command came as a pair. The title is a survival from the days when the dictator commanded the infantry in person and needed someone to lead the cavalry.',
    ],
    [
        'text' => 'What happened if a year began with no curule magistrate in office?',
        'correct' => 3,
        'options' => [
            'The outgoing consuls stayed in office until an election could be held',
            'The Senate governed directly until the next elections',
            'A dictator was appointed automatically to hold the elections',
            'A series of interreges was appointed, each for five days, to hold the elections',
        ],
        'explanation' => 'With no magistrate in office the auspices reverted to the patrician senators, who named an interrex for five days, then another, until consuls could be elected. The device kept the constitutional chain unbroken: there was never a moment at which nobody held the right to consult the gods on the state\'s behalf.',
    ],

];
