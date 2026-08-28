<?php

declare(strict_types=1);

/**
 * Question fixtures for the quiz expanded from "War, Reform and Collapse", the accepted
 * proposal for the source "the-punic-wars-and-the-fall-of-the-republic.pdf".
 *
 * The sections follow that proposal's outline in order, eight questions each. Every
 * question offers four options, of which the one at `correct` is the answer; the other
 * three are meant to be genuinely tempting to someone who has read the material once.
 *
 * @return array<int, array{text: string, correct: int, options: array<int, string>, explanation: string}>
 */
return [

    // The wars with Carthage.

    [
        'text' => 'Control of which island was at stake when the First Punic War broke out?',
        'correct' => 2,
        'options' => [
            'Sardinia',
            'Corsica',
            'Sicily',
            'Malta',
        ],
        'explanation' => 'The war began over Messana in north-eastern Sicily and was fought largely for the island, which became Rome\'s first province in 241 BC. Sardinia and Corsica were seized a few years later, while Carthage was too weak to resist, but they were not the cause of the war.',
    ],
    [
        'text' => 'In which years was the First Punic War fought?',
        'correct' => 0,
        'options' => [
            '264 to 241 BC',
            '218 to 201 BC',
            '149 to 146 BC',
            '280 to 275 BC',
        ],
        'explanation' => 'The first war ran for twenty-three years, from 264 to 241 BC, which makes it the longest continuous war Rome had fought to that point. The other spans belong to the second war, the third war, and the war against Pyrrhus that immediately preceded the quarrel with Carthage.',
    ],
    [
        'text' => 'Which strategy is Quintus Fabius Maximus remembered for adopting against Hannibal in Italy?',
        'correct' => 1,
        'options' => [
            'Massing every available legion for one decisive battle',
            'Shadowing Hannibal\'s army and refusing to give battle',
            'Withdrawing the legions behind the walls of Rome',
            'Transferring the war immediately to Africa',
        ],
        'explanation' => 'Fabius kept his army close enough to limit Hannibal\'s movements and never close enough to be forced into a pitched fight, which earned him both the nickname Cunctator, the Delayer, and considerable contempt at the time. The policy was abandoned in favour of a decisive battle at Cannae, and Cannae is what vindicated it.',
    ],
    [
        'text' => 'What was the corvus that the Romans fitted to their warships?',
        'correct' => 3,
        'options' => [
            'A reinforced ram mounted at the waterline',
            'A tower from which signals were passed along the fleet',
            'A light, fast ship used for scouting ahead of the fleet',
            'A hinged boarding bridge that pinned an enemy ship alongside',
        ],
        'explanation' => 'Rome had no naval tradition and could not out-manoeuvre Carthaginian crews, so it changed the terms of the fight: the corvus dropped onto an enemy deck and let legionaries board. It converted a sea battle into the kind of infantry action Rome was already good at, at the cost of making the ships dangerously top-heavy.',
    ],
    [
        'text' => 'Which Carthaginian commander crossed the Alps into Italy in 218 BC?',
        'correct' => 0,
        'options' => [
            'Hannibal',
            'Hamilcar Barca',
            'Hasdrubal Barca',
            'Mago',
        ],
        'explanation' => 'Hannibal made the crossing at the outset of the second war and stayed in Italy for fifteen years. His brother Hasdrubal is the most plausible wrong answer, since he attempted the same march in 207 BC to reinforce him and was intercepted and killed at the Metaurus.',
    ],
    [
        'text' => 'At which battle in 216 BC did Hannibal surround and destroy a much larger Roman army?',
        'correct' => 2,
        'options' => [
            'The Trebia',
            'Lake Trasimene',
            'Cannae',
            'Zama',
        ],
        'explanation' => 'At Cannae Hannibal let his centre give ground while his flanks held, closing around a Roman army perhaps twice the size of his own. The Trebia and Trasimene were also his victories, in 218 and 217, but Cannae was the envelopment and the heaviest single defeat in Roman history.',
    ],
    [
        'text' => 'Which Roman commander defeated Hannibal at Zama in 202 BC?',
        'correct' => 1,
        'options' => [
            'Quintus Fabius Maximus',
            'Publius Cornelius Scipio',
            'Marcus Claudius Marcellus',
            'Lucius Aemilius Paullus',
        ],
        'explanation' => 'Scipio took the war to Africa and forced Carthage to recall Hannibal from Italy to defend the city, then beat him at Zama and took the name Africanus. Marcellus and Paullus were prominent commanders of the same war, but Paullus fell at Cannae and Marcellus fought chiefly in Sicily.',
    ],
    [
        'text' => 'What became of Carthage at the end of the Third Punic War in 146 BC?',
        'correct' => 3,
        'options' => [
            'It kept its walls but surrendered its fleet and its overseas possessions',
            'It became an allied city, governing itself under Roman supervision',
            'It was garrisoned and left standing as a Roman naval base',
            'It was destroyed and its territory became the province of Africa',
        ],
        'explanation' => 'The city was taken after a three-year siege, systematically destroyed, and its land annexed as the province of Africa. The first option describes the settlement of 201 BC after the second war, which is exactly the arrangement the third war was fought to replace.',
    ],

    // The land problem.

    [
        'text' => 'What was ager publicus?',
        'correct' => 0,
        'options' => [
            'Land taken from defeated communities and held by the Roman state',
            'Land owned outright by senators and worked by their households',
            'The common pasture belonging to an Italian village',
            'Land dedicated to a temple and administered by its priests',
        ],
        'explanation' => 'Conquest in Italy left the state holding large tracts of confiscated land, which citizens were allowed to occupy for a rent. Because the occupier never became the owner, the state retained the right to take it back, and that right is what every land reform of the next century turned on.',
    ],
    [
        'text' => 'What was a latifundium?',
        'correct' => 2,
        'options' => [
            'A smallholding farmed by a citizen liable for military service',
            'A colony of veterans planted on land taken in war',
            'A large estate worked chiefly by slave labour',
            'A farm rented from the state by a whole tribe together',
        ],
        'explanation' => 'The latifundia were consolidated estates, run for the market with gangs of slaves rather than by a family working its own ground. Their spread is the mechanism by which land that had supported many liable smallholders came to support one wealthy owner and a workforce that owed the state no service at all.',
    ],
    [
        'text' => 'Why did prolonged military service damage the class of small farmers?',
        'correct' => 1,
        'options' => [
            'Serving soldiers were forbidden by law to hold land',
            'Long campaigns kept farmers from their land, which fell into debt and was bought up',
            'Military pay was taxed at a rate smallholders could not meet',
            'Service abroad cancelled a man\'s registration in a rural tribe',
        ],
        'explanation' => 'A campaign in Spain or Greece could keep a man away for years, and a farm without its farmer did not survive the absence. The land was sold or simply abandoned, and the buyer was almost always a neighbour with the capital to add it to an existing estate.',
    ],
    [
        'text' => 'Where did the labour that worked the great estates come from?',
        'correct' => 3,
        'options' => [
            'Tenant farmers brought in from the provinces',
            'Landless citizens hired for a daily wage',
            'Prisoners condemned to labour by the courts',
            'Slaves taken in the wars of conquest',
        ],
        'explanation' => 'Victory abroad supplied captives in numbers that made slave labour cheaper than the alternatives, so the same wars that ruined the smallholder also provided the workforce that replaced him. The two halves of the problem had a single cause, which is why treating them separately never worked.',
    ],
    [
        'text' => 'Why did the decline of the smallholder threaten the army itself?',
        'correct' => 0,
        'options' => [
            'Service was tied to a property qualification, so fewer men were eligible for the levy',
            'Only smallholders were registered in the centuriate assembly',
            'Landless citizens were forbidden to leave the city of Rome',
            'The legions were levied by tribe, and the rural tribes had lost their voting rights',
        ],
        'explanation' => 'The legions were raised from the assidui, citizens assessed above a minimum property threshold, on the reasoning that a man with something to lose would fight for it. Every farm absorbed into an estate therefore removed a soldier from the roll, and the qualification had to be lowered repeatedly before it was abandoned altogether.',
    ],
    [
        'text' => 'Where did the dispossessed farmers largely end up?',
        'correct' => 2,
        'options' => [
            'In the provinces, as colonists on newly annexed land',
            'On the estates that had absorbed their farms, as paid labourers',
            'In Rome, swelling a landless urban population',
            'In the fleet, which took men below the property qualification',
        ],
        'explanation' => 'They drifted to the city, where they retained the vote but had no property and no employment that slave labour had not already taken. That population is the constituency every popular politician from the Gracchi onwards had to address, and the reason the grain supply became a political question.',
    ],
    [
        'text' => 'What did it mean for a citizen to be counted among the capite censi?',
        'correct' => 1,
        'options' => [
            'That the censors had struck him from the citizen roll for misconduct',
            'That he was registered by a head count, holding property below the census minimum',
            'That he was a freedman enrolled in one of the four urban tribes',
            'That he was a provincial holding Latin rights rather than full citizenship',
        ],
        'explanation' => 'Those "counted by head" had too little property to be assessed into a class, so they were registered as persons rather than as taxable holdings. They were citizens with votes but, until Marius, not soldiers, which is what makes the change in recruitment such a significant one.',
    ],
    [
        'text' => 'Which earlier legislation had already tried to cap how much public land a single man could occupy?',
        'correct' => 3,
        'options' => [
            'The lex Hortensia of 287 BC',
            'The Twelve Tables',
            'The lex Villia annalis of 180 BC',
            'The Licinio-Sextian laws of 367 BC',
        ],
        'explanation' => 'The Licinio-Sextian laws are reported to have limited holdings of public land to five hundred iugera. The limit was old, well known and comprehensively ignored, which is why Tiberius Gracchus could present his bill as the enforcement of existing law rather than as an innovation.',
    ],

    // The Gracchi.

    [
        'text' => 'Which office did Tiberius Gracchus hold when he brought forward his land bill in 133 BC?',
        'correct' => 2,
        'options' => [
            'Consul',
            'Praetor',
            'Tribune of the plebs',
            'Censor',
        ],
        'explanation' => 'He was tribune, which mattered for the method as much as the man: a tribune could put a bill straight to the plebeian assembly without the Senate\'s prior approval. Using that route on a major question of state, rather than as a last resort, is what made the proposal a constitutional crisis and not merely a contested reform.',
    ],
    [
        'text' => 'What did Tiberius Gracchus\' land bill propose?',
        'correct' => 0,
        'options' => [
            'Enforcing the limit on public land and redistributing the surplus in small lots',
            'Cancelling the debts of citizens who had lost their farms',
            'Granting Roman citizenship to the Italian allies',
            'Founding colonies of citizens in the overseas provinces',
        ],
        'explanation' => 'The bill revived the old cap on occupation of public land and set up a commission to reassign what exceeded it to landless citizens as inalienable smallholdings. The point was to rebuild the class the levy depended on, which is why a measure about land was in substance a measure about the army.',
    ],
    [
        'text' => 'How did Tiberius Gracchus overcome the veto of his fellow tribune Octavius?',
        'correct' => 1,
        'options' => [
            'He obtained a decree of the Senate setting the veto aside',
            'He had the assembly vote Octavius out of office',
            'He put the bill to the centuriate assembly instead',
            'He referred the dispute to the college of pontiffs',
        ],
        'explanation' => 'He argued that a tribune who acted against the interests of the plebs had ceased to be a tribune, and had the assembly depose him. It worked, and it was the more damaging act of the two: the tribunate\'s protective power depended on being absolute, and Tiberius had just shown that a majority could remove it.',
    ],
    [
        'text' => 'What did Tiberius Gracchus propose to do with the bequest of Attalus III of Pergamum?',
        'correct' => 3,
        'options' => [
            'Refuse it, to avoid entanglement in Asia',
            'Use it to pay the armies then serving in Spain',
            'Distribute it directly to the urban poor as a cash payment',
            'Have the assembly assign it to equip the new smallholders',
        ],
        'explanation' => 'By proposing that the people dispose of a foreign king\'s legacy, he reached into the two areas the Senate controlled most firmly, finance and foreign affairs, without consulting it. The land bill made him enemies; this proposal convinced many senators that he intended to govern through the assembly permanently.',
    ],
    [
        'text' => 'How did Tiberius Gracchus die?',
        'correct' => 2,
        'options' => [
            'He was condemned by a special court and executed',
            'He was killed in fighting in Spain shortly after his tribunate',
            'He was beaten to death by senators and their followers while seeking a second tribunate',
            'He took his own life when his land commission was dissolved',
        ],
        'explanation' => 'A group led by the pontifex maximus killed him and some three hundred of his supporters in 133 BC, when he stood for an immediate second term. It was the first political bloodshed in Rome for centuries, and it established that a domestic dispute could now be settled by killing rather than by voting.',
    ],
    [
        'text' => 'Which measure of Gaius Gracchus committed the state to a standing obligation towards the urban poor?',
        'correct' => 0,
        'options' => [
            'The law selling grain to citizens at a fixed low price',
            'The law reviving his brother\'s land commission',
            'The law founding a citizen colony on the site of Carthage',
            'The law transferring the extortion court to the equestrians',
        ],
        'explanation' => 'The grain law did not give corn away, but it fixed the price below the market and made the supply a permanent charge on the treasury. Once the state had accepted that duty no politician could withdraw it, and the urban grain supply stayed a live political question until the end of the Republic.',
    ],
    [
        'text' => 'To which group did Gaius Gracchus transfer control of the court that tried provincial extortion?',
        'correct' => 1,
        'options' => [
            'The plebeian assembly',
            'The equestrian order',
            'The tribunes of the plebs',
            'The censors',
        ],
        'explanation' => 'Moving the juries from senators to equestrians ended the arrangement in which senatorial governors were judged by other senators. It also gave the equestrian order a standing weapon against provincial governors and a permanent interest in the quarrel, which is why control of the courts was fought over for the next fifty years.',
    ],
    [
        'text' => 'What instrument did the Senate use against Gaius Gracchus in 121 BC?',
        'correct' => 3,
        'options' => [
            'The appointment of a dictator',
            'A tribune\'s veto of his legislation',
            'A special court set up to try him for treason',
            'The final decree, calling on the consuls to protect the state',
        ],
        'explanation' => 'The senatus consultum ultimum was used here for the first time, and Gaius and thousands of his supporters died. Its significance is that it created no new legal authority: it simply announced that the Senate would not object to whatever the consul did next, which is why its legality was disputed ever afterwards.',
    ],

    // Marius and Sulla.

    [
        'text' => 'Which change in recruitment is associated with Gaius Marius?',
        'correct' => 2,
        'options' => [
            'Extending service to the Italian allies as full citizens',
            'Making military service compulsory for every adult male citizen',
            'Enrolling volunteers who fell below the property qualification',
            'Paying the legions from the commander\'s own resources',
        ],
        'explanation' => 'Marius took men with no property into the legions, which solved the recruitment shortage at a stroke by drawing on the population the land crisis had created. It also cut the last link between owning land and bearing arms, and so raised a question nobody had needed to answer before: what a soldier was to live on when the war ended.',
    ],
    [
        'text' => 'What followed from the enrolment of landless volunteers into the legions?',
        'correct' => 0,
        'options' => [
            'Soldiers looked to their commander, not the state, to secure land on discharge',
            'The Senate took direct charge of recruitment for the first time',
            'Legions became markedly smaller and shorter-lived',
            'Terms of service were shortened to a single campaign',
        ],
        'explanation' => 'A soldier with a farm went home to it; a soldier without one needed a settlement, and only his general had the standing to obtain it from the Senate. The army therefore acquired an interest in its commander\'s political success, and armies with such an interest are what the following sixty years are made of.',
    ],
    [
        'text' => 'How many consulships did Marius hold?',
        'correct' => 3,
        'options' => [
            'Three',
            'Four',
            'Five',
            'Seven',
        ],
        'explanation' => 'Seven in all, including five in unbroken succession from 104 to 100 BC during the emergency of the Cimbri and Teutones. The rules on intervals between offices simply gave way in the face of a military crisis, which showed how much of the constitution rested on custom rather than on enforcement.',
    ],
    [
        'text' => 'What was the Social War of 91 to 88 BC fought over?',
        'correct' => 1,
        'options' => [
            'The redistribution of public land in Etruria and Campania',
            'The Italian allies\' demand for Roman citizenship',
            'The command of the war against Mithridates',
            'The right of the plebs to hold the consulship',
        ],
        'explanation' => 'The allies had supplied much of Rome\'s manpower for generations without the citizenship, and when a tribune proposing to grant it was murdered, they revolted. Rome won the war and then conceded the demand, which was the practical admission that the claim had been just.',
    ],
    [
        'text' => 'Which command did Marius and Sulla quarrel over in 88 BC?',
        'correct' => 2,
        'options' => [
            'The war against Jugurtha in Numidia',
            'The suppression of the slave revolt in Sicily',
            'The war against Mithridates of Pontus',
            'The command of the armies in Spain',
        ],
        'explanation' => 'The Senate had given the eastern command to Sulla as consul; a tribune then had the assembly transfer it to Marius. The dispute is the hinge of the period, because it put the question of who allocated commands to a test that neither institution could win on argument alone.',
    ],
    [
        'text' => 'What did Sulla do in 88 BC that no Roman commander had done before?',
        'correct' => 0,
        'options' => [
            'He marched his army on Rome itself',
            'He had himself appointed dictator without a fixed term',
            'He published lists of citizens who could be killed with impunity',
            'He refused to lay down a command when ordered to do so',
        ],
        'explanation' => 'Sulla went to his legions and led them against the city, and all but one of his officers refused to go with him. He did each of the other three things as well, but later; the march on Rome came first and made the rest conceivable.',
    ],
    [
        'text' => 'What were the proscriptions?',
        'correct' => 3,
        'options' => [
            'The removal of senators from the roll by the censors',
            'The exile of magistrates convicted of extortion in the provinces',
            'The confiscation of Italian land to settle discharged veterans',
            'Published lists of citizens who could be killed for a reward, their property forfeit',
        ],
        'explanation' => 'Sulla posted names in the Forum: anyone on the list could be killed by anyone at all, the killer was paid, and the estate went to the treasury or to Sulla\'s supporters. Making the killing lawful, public and profitable is what distinguishes a proscription from a purge, and it was used again by the triumvirs in 43 BC.',
    ],
    [
        'text' => 'What was unprecedented about the dictatorship Sulla assumed?',
        'correct' => 1,
        'options' => [
            'He was the first man to hold the office in over a century',
            'He held it with no fixed term, to remake the constitution',
            'He shared it with a colleague of equal power',
            'He held it while also serving as consul',
        ],
        'explanation' => 'The office had always been bounded at six months and directed at a specific emergency; Sulla took it to write laws and reconstitute the state, with no end date. He did resign it voluntarily in 79 BC, which is the detail that lets the episode be read either as a restoration or as the model for everything that followed.',
    ],

    // The end of the Republic.

    [
        'text' => 'Who formed the political alliance of 60 BC known as the First Triumvirate?',
        'correct' => 2,
        'options' => [
            'Sulla, Pompey and Crassus',
            'Caesar, Antony and Lepidus',
            'Caesar, Pompey and Crassus',
            'Pompey, Cicero and Cato',
        ],
        'explanation' => 'Caesar wanted the consulship and a command, Pompey wanted land for his veterans and confirmation of his eastern settlement, and Crassus wanted relief for the tax contractors he backed. Each had been blocked by the Senate separately; together they were able to get everything. Caesar, Antony and Lepidus formed the second triumvirate seventeen years later.',
    ],
    [
        'text' => 'What was the First Triumvirate in constitutional terms?',
        'correct' => 0,
        'options' => [
            'A private agreement with no legal standing whatever',
            'A magistracy created for three men by a law of the assembly',
            'A commission of three appointed by the Senate',
            'A board of three elected annually by the tribal assembly',
        ],
        'explanation' => 'It was an understanding between three men, unknown to the constitution and for a time unknown to almost everyone else. The contrast with the Second Triumvirate is the instructive one: that arrangement was established by the lex Titia in 43 BC and gave its members formal powers to reconstitute the state.',
    ],
    [
        'text' => 'Which river did Caesar cross in 49 BC to begin the civil war?',
        'correct' => 3,
        'options' => [
            'The Tiber',
            'The Po',
            'The Arno',
            'The Rubicon',
        ],
        'explanation' => 'The Rubicon marked the southern boundary of his province. A commander who crossed it under arms entered Italy without having laid down his imperium, which was in itself an act of war, so the crossing was the decision rather than a step towards it.',
    ],
    [
        'text' => 'At which battle did Caesar defeat Pompey in 48 BC?',
        'correct' => 1,
        'options' => [
            'Thapsus',
            'Pharsalus',
            'Munda',
            'Philippi',
        ],
        'explanation' => 'Pharsalus in Thessaly broke Pompey\'s army and sent him to Egypt, where he was killed. Thapsus and Munda were Caesar\'s later victories over the remaining Pompeian forces in Africa and Spain, and Philippi in 42 BC was fought after his death, against his assassins.',
    ],
    [
        'text' => 'Which position did Caesar accept in 44 BC that most alarmed his opponents?',
        'correct' => 2,
        'options' => [
            'Consul for the fifth time',
            'Censor for life',
            'Dictator in perpetuity',
            'Tribune of the plebs for life',
        ],
        'explanation' => 'Dictator perpetuo removed the last thing that had made the dictatorship tolerable, its end date, and made permanent what the constitution had defined as temporary. Because the office was legitimate and the tenure was not, it left opponents with no constitutional remedy, which is the argument the conspirators made for using a knife.',
    ],
    [
        'text' => 'Who led the conspiracy that killed Caesar in March 44 BC?',
        'correct' => 0,
        'options' => [
            'Marcus Brutus and Gaius Cassius',
            'Mark Antony and Lepidus',
            'Cato and Cicero',
            'Octavian and Agrippa',
        ],
        'explanation' => 'Brutus and Cassius led some sixty conspirators, most of them senators and many of them men Caesar had pardoned after the civil war. Cicero approved of the act afterwards but was not party to it, and Cato had already died in Africa the year before.',
    ],
    [
        'text' => 'At which battle in 31 BC did Octavian defeat Antony and Cleopatra?',
        'correct' => 3,
        'options' => [
            'Philippi',
            'Mutina',
            'Pharsalus',
            'Actium',
        ],
        'explanation' => 'The naval action off Actium in western Greece ended the last of the civil wars and left Octavian without a rival. Philippi and Mutina belong to the fighting after Caesar\'s death, and Pharsalus to the war between Caesar and Pompey.',
    ],
    [
        'text' => 'What did the settlement of 27 BC do?',
        'correct' => 1,
        'options' => [
            'Abolished the Senate and the annual magistracies outright',
            'Left the republican offices standing while concentrating real power in one man',
            'Restored the Senate to genuine control of policy and the armies',
            'Established a monarchy with a formal royal title',
        ],
        'explanation' => 'Octavian handed the provinces back to the Senate and people, received the name Augustus, and kept the provinces where the legions were stationed. Consuls continued to be elected and the Senate continued to meet; what had changed was that one man commanded almost the whole army, which is why the forms could safely be preserved.',
    ],

];
