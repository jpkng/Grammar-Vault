const QUESTIONS_TEXT = `
# Grammar Vault - Questions File
# Format:
#   CATEGORY: <category name>        <- groups questions for a level/NPC type
#   Q: <question text>
#   TYPE: MC | TYPED                 <- optional. Defaults to MC if omitted.
#
#   -- Multiple Choice (TYPE: MC, or no TYPE line at all) --
#   O: <option A>
#   O: <option B>
#   O: <option C>
#   O: <option D>
#   A: <index of correct answer, 0-based>
#   E: <explanation shown as feedback>
#   ---                              <- separator between questions
#
#   -- Typed Answer (TYPE: TYPED) -- used for Fill-in-the-Blank,
#      Identification, Spelling, Reading Comprehension short-answer, etc.
#      No O: lines. Player types their answer instead of picking one.
#   TYPE: TYPED
#   A: <accepted answer>|<alternate accepted answer>|...   <- pipe-separate
#        multiple acceptable answers/spellings. Matching is case-insensitive
#        and ignores extra whitespace.
#   E: <explanation shown as feedback>
#   ---
#
# Lines starting with # are comments and are ignored.
# Blank lines are also ignored.
# Mix TYPE: MC and TYPE: TYPED questions freely within any CATEGORY —
# not every question needs to be multiple choice.
# ══════════════════════════════════════════════════════
# CATEGORY: Grammar
# ══════════════════════════════════════════════════════
CATEGORY: Grammar
Q: Which word in this sentence is a noun: 'The dog ran fast.'
TOPIC: nouns
O: dog
O: ran
O: fast
O: the
A: 0
E: 'Dog' is a noun — it names an animal. Nouns name people, places, animals, or things.
---
Q: Which word in this sentence is a noun: 'My sister plays soccer.'
TOPIC: nouns
O: my
O: plays
O: sister
O: fast
A: 2
E: 'Sister' is a noun — it names a person. Nouns name people, places, or things.
---
Q: Which word is a noun in: 'The bird sat on the branch.'
TOPIC: nouns
O: sat
O: on
O: branch
O: the
A: 2
E: 'Branch' is a noun — it names a thing (part of a tree).
---
Q: Which word is a noun in: 'We visited the zoo yesterday.'
TOPIC: nouns
O: visited
O: zoo
O: yesterday
O: we
A: 1
E: 'Zoo' is a noun — it names a place.
---
Q: Which word is a noun in: 'Her happy laugh filled the room.'
TOPIC: nouns
O: happy
O: laugh
O: filled
O: the
A: 1
E: 'Laugh' is a noun here — it names a thing (the sound she made).
---
Q: Which word is a noun in: 'The teacher gave us candy.'
TOPIC: nouns
O: gave
O: us
O: candy
O: the
A: 2
E: 'Candy' is a noun — it names a thing.
---
Q: Which word is a noun in: 'Ben and Lucy walked to the park.'
TOPIC: nouns
O: walked
O: park
O: to
O: and
A: 1
E: 'Park' is a noun — it names a place.
---
Q: Which word is a noun in: 'The tall giraffe ate leaves.'
TOPIC: nouns
O: tall
O: giraffe
O: ate
O: the
A: 1
E: 'Giraffe' is a noun — it names an animal.
---
Q: Which word is the verb (action word) in: 'The children played outside.'
TOPIC: verbs
O: children
O: played
O: outside
O: the
A: 1
E: 'Played' is the verb — it tells what the children did.
---
Q: Which word is the verb in: 'Sam jumps over the puddle.'
TOPIC: verbs
O: sam
O: jumps
O: over
O: puddle
A: 1
E: 'Jumps' is the verb — it shows the action Sam does.
---
Q: Which word is the verb in: 'The cat sleeps on the couch.'
TOPIC: verbs
O: cat
O: sleeps
O: on
O: couch
A: 1
E: 'Sleeps' is the verb — it tells what the cat does.
---
Q: Which word is the verb in: 'We ate pancakes for breakfast.'
TOPIC: verbs
O: we
O: ate
O: pancakes
O: breakfast
A: 1
E: 'Ate' is the verb — it shows the action.
---
Q: Which word is the verb in: 'The rain fell all night.'
TOPIC: verbs
O: rain
O: fell
O: all
O: night
A: 1
E: 'Fell' is the verb — it tells what the rain did.
---
Q: Which word is the verb in: 'My dad bakes bread every Sunday.'
TOPIC: verbs
O: dad
O: bakes
O: bread
O: sunday
A: 1
E: 'Bakes' is the verb — it shows the action my dad does.
---
Q: Which word is the verb in: 'The kids laughed at the funny joke.'
TOPIC: verbs
O: kids
O: laughed
O: funny
O: joke
A: 1
E: 'Laughed' is the verb — it tells what the kids did.
---
Q: Which word is the verb in: 'She draws pictures of flowers.'
TOPIC: verbs
O: she
O: draws
O: pictures
O: flowers
A: 1
E: 'Draws' is the verb — it shows the action she does.
---
Q: Which word is an adjective in: 'The tiny kitten slept quietly.'
TOPIC: adjectives
O: kitten
O: slept
O: tiny
O: quietly
A: 2
E: 'Tiny' is an adjective — it describes the kitten. Adjectives describe nouns.
---
Q: Which word is an adjective in: 'I saw a bright red balloon.'
TOPIC: adjectives
O: saw
O: balloon
O: bright
O: i
A: 2
E: 'Bright' is an adjective — it describes what kind of balloon.
---
Q: Which word is an adjective in: 'The tall boy waved to us.'
TOPIC: adjectives
O: tall
O: boy
O: waved
O: us
A: 0
E: 'Tall' is an adjective — it tells us more about the boy.
---
Q: Which word is an adjective in: 'We ate a delicious apple pie.'
TOPIC: adjectives
O: ate
O: delicious
O: apple pie
O: we
A: 1
E: 'Delicious' is an adjective — it describes the pie.
---
Q: Which word is an adjective in: 'The old house had a creaky door.'
TOPIC: adjectives
O: house
O: old
O: had
O: door
A: 1
E: 'Old' is an adjective — it describes the house.
---
Q: Which word is an adjective in: 'She wore a soft, warm sweater.'
TOPIC: adjectives
O: wore
O: soft
O: sweater
O: she
A: 1
E: 'Soft' is an adjective — it describes the sweater.
---
Q: Which word is an adjective in: 'A loud thunderclap scared the dog.'
TOPIC: adjectives
O: loud
O: thunderclap
O: scared
O: dog
A: 0
E: 'Loud' is an adjective — it describes the thunderclap.
---
Q: Which word is an adjective in: 'The three puppies chased a shiny ball.'
TOPIC: adjectives
O: puppies
O: chased
O: shiny
O: ball
A: 2
E: 'Shiny' is an adjective — it describes the ball.
---
Q: Choose the correct pronoun: '___ is my best friend.' (talking about a girl named Kate)
TOPIC: pronouns
O: He
O: She
O: It
O: They
A: 1
E: 'She' is correct because Kate is a girl. Use 'she' to replace a girl's or woman's name.
---
Q: Choose the correct pronoun: 'Tom lost his hat, so ___ looked everywhere for it.'
TOPIC: pronouns
O: she
O: they
O: he
O: it
A: 2
E: 'He' is correct because Tom is a boy. Use 'he' to replace a boy's or man's name.
---
Q: Choose the correct pronoun: 'The puppies are hungry. ___ want food.'
TOPIC: pronouns
O: It
O: She
O: He
O: They
A: 3
E: 'They' is correct because 'puppies' is plural (more than one).
---
Q: Choose the correct pronoun: 'The ball rolled away. ___ went under the car.'
TOPIC: pronouns
O: It
O: He
O: She
O: They
A: 0
E: 'It' is correct for a thing, like a ball. Use 'it' for objects and animals when gender is unknown.
---
Q: Which pronoun replaces 'Maria and I' in: 'Maria and I went to the store.'
TOPIC: pronouns
O: They
O: We
O: You
O: He
A: 1
E: 'We' replaces 'Maria and I' because it includes the speaker and another person.
---
Q: Choose the correct pronoun: 'This book belongs to ___.' (talking about yourself)
TOPIC: pronouns
O: you
O: him
O: me
O: they
A: 2
E: 'Me' is correct when talking about yourself as the object of the sentence.
---
Q: Which word is a pronoun in: 'She gave the toy to him.'
TOPIC: pronouns
O: gave
O: toy
O: she
O: to
A: 2
E: 'She' is a pronoun — it takes the place of a person's name.
---
Q: Choose the correct pronoun: 'The teacher asked the students to raise ___ hands.'
TOPIC: pronouns
O: his
O: her
O: their
O: its
A: 2
E: 'Their' is correct because 'students' is plural.
---
Q: Choose the correct verb: Yesterday, I ___ to the park.
TOPIC: tenses
O: walk
O: walks
O: walked
O: walking
A: 2
E: 'Walked' is the past tense verb. Add -ed to show something already happened.
---
Q: Choose the correct verb: Right now, she ___ her homework.
TOPIC: tenses
O: do
O: does
O: did
O: doing
A: 1
E: 'Does' is correct for present tense with he/she/it. Add -s or -es to the verb.
---
Q: Choose the correct verb: Tomorrow, we ___ to the beach.
TOPIC: tenses
O: went
O: go
O: will go
O: goes
A: 2
E: 'Will go' shows future tense — something that hasn't happened yet.
---
Q: Choose the correct verb: Every morning, the rooster ___ loudly.
TOPIC: tenses
O: crow
O: crows
O: crowed
O: crowing
A: 1
E: 'Crows' is correct present tense — add -s for he/she/it (the rooster).
---
Q: Choose the correct verb: Last night, we ___ a movie.
TOPIC: tenses
O: watch
O: watches
O: watched
O: will watch
A: 2
E: 'Watched' is past tense, shown by the -ed ending, because it happened last night.
---
Q: Choose the correct verb: Next week, they ___ their grandma.
TOPIC: tenses
O: visited
O: visits
O: will visit
O: visiting
A: 2
E: 'Will visit' is future tense — the visit hasn't happened yet.
---
Q: Choose the correct verb: Right now, the baby ___ happily.
TOPIC: tenses
O: laugh
O: laughs
O: laughed
O: will laugh
A: 1
E: 'Laughs' is present tense — add -s for he/she/it (the baby).
---
Q: Choose the correct verb: Two days ago, I ___ my favorite book.
TOPIC: tenses
O: finish
O: finishes
O: finished
O: will finish
A: 2
E: 'Finished' is past tense, shown by the -ed ending.
---
Q: Fill in the blank with the past tense of 'run' (an irregular verb): Yesterday, she ___ very fast.
TOPIC: tenses
TYPE: TYPED
A: ran
E: 'Ran' is the irregular past tense of 'run'. Some verbs change spelling instead of adding -ed.
---
Q: Fill in the blank with the past tense of 'eat' (an irregular verb): Last night, we ___ pizza.
TOPIC: tenses
TYPE: TYPED
A: ate
E: 'Ate' is the irregular past tense of 'eat'. It doesn't just add -ed.
---
Q: Write the plural form of 'cat' (a regular noun).
TOPIC: plurals
TYPE: TYPED
A: cats
E: 'Cat' is a regular noun. Just add -s to make it plural: cat -> cats.
---
Q: Write the plural form of 'box' (a regular noun).
TOPIC: plurals
TYPE: TYPED
A: boxes
E: 'Box' is a regular noun. Add -es to nouns ending in x, s, sh, or ch: box -> boxes.
---
Q: Write the plural form of 'baby' (a regular noun).
TOPIC: plurals
TYPE: TYPED
A: babies
E: 'Baby' is a regular noun. When a noun ends in a consonant + y, change y to i and add -es: baby -> babies.
---
Q: Write the plural form of 'child' (an irregular noun).
TOPIC: plurals
TYPE: TYPED
A: children
E: 'Child' is an irregular noun — its plural is 'children', not 'childs'.
---
Q: Write the plural form of 'mouse' (the animal, an irregular noun).
TOPIC: plurals
TYPE: TYPED
A: mice
E: 'Mouse' is irregular — its plural is 'mice'.
---
Q: Write the plural form of 'foot' (an irregular noun).
TOPIC: plurals
TYPE: TYPED
A: feet
E: 'Foot' is irregular — its plural is 'feet', not 'foots'.
---
Q: Write the plural form of 'bus' (a regular noun).
TOPIC: plurals
TYPE: TYPED
A: buses
E: 'Bus' is a regular noun. Add -es to nouns ending in s: bus -> buses.
---
Q: Write the plural form of 'wolf' (an irregular noun).
TOPIC: plurals
TYPE: TYPED
A: wolves
E: 'Wolf' is an irregular noun. Instead of just adding -s, the f changes to -ves: wolf -> wolves.
---
Q: Write the plural form of 'tooth' (an irregular noun).
TOPIC: plurals
TYPE: TYPED
A: teeth
E: 'Tooth' is irregular — its plural is 'teeth'.
---
Q: Write the plural form of 'toy' (a regular noun).
TOPIC: plurals
TYPE: TYPED
A: toys
E: 'Toy' is a regular noun. When a noun ends in a vowel + y, just add -s: toy -> toys.
---
Q: What kind of sentence is this: 'Please close the door.'
TOPIC: sentence-types
O: Question
O: Command
O: Exclamation
O: Statement
A: 1
E: This is a command — it tells someone to do something. Commands often start with a verb.
---
Q: What kind of sentence is this: 'Watch out for that puddle!'
TOPIC: sentence-types
O: Statement
O: Question
O: Exclamation
O: Command
A: 2
E: This is an exclamation — it shows strong feeling and ends with an exclamation mark.
---
Q: What kind of sentence is this: 'Where did you put my shoes?'
TOPIC: sentence-types
O: Statement
O: Question
O: Command
O: Exclamation
A: 1
E: This is a question — it asks something and ends with a question mark.
---
Q: What kind of sentence is this: 'The sun rises in the east.'
TOPIC: sentence-types
O: Statement
O: Question
O: Command
O: Exclamation
A: 0
E: This is a statement — it tells a fact and ends with a period.
---
Q: What kind of sentence is this: 'Wow, that rocket is huge!'
TOPIC: sentence-types
O: Question
O: Statement
O: Exclamation
O: Command
A: 2
E: This is an exclamation — it shows excitement or surprise.
---
Q: What kind of sentence is this: 'Turn off the lights before you leave.'
TOPIC: sentence-types
O: Command
O: Question
O: Statement
O: Exclamation
A: 0
E: This is a command — it gives an instruction.
---
Q: What kind of sentence is this: 'Do you like chocolate ice cream?'
TOPIC: sentence-types
O: Command
O: Statement
O: Question
O: Exclamation
A: 2
E: This is a question — it asks for information.
---
Q: What kind of sentence is this: 'My birthday is in July.'
TOPIC: sentence-types
O: Statement
O: Question
O: Command
O: Exclamation
A: 0
E: This is a statement — it shares a fact about the speaker.
---
# ══════════════════════════════════════════════════════
# CATEGORY: Punctuation
# ══════════════════════════════════════════════════════
CATEGORY: Punctuation
Q: Which end mark belongs here: 'I love my new puppy___'
TOPIC: end-marks
O: .
O: ?
O: !
O: ,
A: 0
E: A period (.) ends a sentence that tells something (a statement).
---
Q: Which end mark belongs here: 'What time does the movie start___'
TOPIC: end-marks
O: .
O: !
O: ?
O: ,
A: 2
E: A question mark (?) ends a sentence that asks something.
---
Q: Which end mark belongs here: 'Watch out, the floor is wet___'
TOPIC: end-marks
O: .
O: ?
O: !
O: ,
A: 2
E: An exclamation mark (!) ends a sentence that shows strong feeling or a warning.
---
Q: Which end mark belongs here: 'My favorite color is blue___'
TOPIC: end-marks
O: .
O: ?
O: !
O: ,
A: 0
E: A period (.) ends a simple statement.
---
Q: Which end mark belongs here: 'Can I borrow your pencil___'
TOPIC: end-marks
O: .
O: !
O: ?
O: ,
A: 2
E: A question mark (?) is needed because this sentence asks something.
---
Q: Which end mark belongs here: 'We won the game___'
TOPIC: end-marks
O: .
O: !
O: ?
O: ,
A: 1
E: An exclamation mark (!) shows excitement, which fits winning a game.
---
Q: Which sentence uses the correct end punctuation?
TOPIC: end-marks
O: Do you have a pet.
O: I have a pet?
O: I have a pet.
O: I have a pet!!
A: 2
E: 'I have a pet.' is a statement, so it correctly ends with a period.
---
Q: Which sentence uses the correct end punctuation?
TOPIC: end-marks
O: Where is the library!
O: Where is the library?
O: Where is the library.
O: Where is the library
A: 1
E: 'Where is the library?' asks a question, so it needs a question mark.
---
Q: Which sentence uses the correct end punctuation?
TOPIC: end-marks
O: Help, the house is on fire.
O: Help, the house is on fire?
O: Help, the house is on fire!
O: Help, the house is on fire
A: 2
E: 'Help, the house is on fire!' shows urgency and strong feeling, so it needs an exclamation mark.
---
Q: Which end mark belongs here: 'Please pass the salt___'
TOPIC: end-marks
TYPE: TYPED
A: .
E: This sentence is a polite request, so it ends with a period.
---
Q: Which sentence uses commas correctly?
TOPIC: commas
O: I like apples bananas and grapes.
O: I like apples, bananas, and grapes.
O: I like, apples bananas, and grapes.
O: I like apples, bananas and, grapes.
A: 1
E: Commas separate each item in a list: apples, bananas, and grapes.
---
Q: Which sentence uses commas correctly?
TOPIC: commas
O: My backpack has pencils, erasers, and a ruler.
O: My backpack has pencils erasers and a ruler.
O: My backpack has, pencils erasers and a ruler.
O: My backpack has pencils, erasers and, a ruler.
A: 0
E: A comma goes after each item in a list except the last one.
---
Q: Which sentence uses commas correctly?
TOPIC: commas
O: We saw lions, tigers, and bears at the zoo.
O: We saw lions tigers and bears at the zoo.
O: We saw lions, tigers and, bears at the zoo.
O: We saw, lions tigers and bears at the zoo.
A: 0
E: Commas keep each animal in the list separate and easy to read.
---
Q: Which sentence uses commas correctly?
TOPIC: commas
O: For breakfast I had eggs toast and juice.
O: For breakfast, I had eggs, toast, and juice.
O: For breakfast I had eggs, toast and juice
O: For breakfast, I had eggs toast, and juice.
A: 1
E: A comma follows the introductory word 'breakfast' phrase, and commas separate each food in the list.
---
Q: Which sentence uses commas correctly?
TOPIC: commas
O: She packed a hat, sunglasses, and sunscreen.
O: She packed a hat sunglasses and sunscreen.
O: She packed, a hat sunglasses, and sunscreen.
O: She packed a hat, sunglasses and, sunscreen.
A: 0
E: Commas separate items in the list: hat, sunglasses, and sunscreen.
---
Q: Which sentence uses commas correctly?
TOPIC: commas
O: My friends Jack, Mia, and Leo came over.
O: My friends Jack Mia and Leo came over.
O: My friends, Jack Mia, and Leo came over.
O: My friends Jack, Mia and, Leo came over.
A: 0
E: Commas separate the names in the list: Jack, Mia, and Leo.
---
Q: Which sentence uses a comma correctly for a date?
TOPIC: commas
O: I was born on May 3 2015.
O: I was born on May 3, 2015.
O: I was born on May, 3 2015.
O: I was born on May 3rd 2015
A: 1
E: A comma goes between the day and the year in a date: May 3, 2015.
---
Q: Which sentence uses a comma correctly for a city and state?
TOPIC: commas
O: We live in Austin Texas.
O: We live in Austin, Texas.
O: We live in, Austin Texas.
O: We live in Austin Texas,
A: 1
E: A comma separates a city from its state: Austin, Texas.
---
Q: Write the contraction for 'do not'.
TOPIC: contractions
TYPE: TYPED
A: don't
E: 'Do not' becomes 'don't' — the apostrophe replaces the missing letter 'o'.
---
Q: Write the contraction for 'I am'.
TOPIC: contractions
TYPE: TYPED
A: i'm
E: 'I am' becomes 'I'm' — the apostrophe replaces the missing letter 'a'.
---
Q: Write the contraction for 'they are'.
TOPIC: contractions
TYPE: TYPED
A: they're
E: 'They are' becomes 'they're' — the apostrophe replaces the missing letter 'a'.
---
Q: Write the contraction for 'cannot'.
TOPIC: contractions
TYPE: TYPED
A: can't
E: 'Cannot' becomes 'can't' — the apostrophe replaces the letters 'no'.
---
Q: Write the contraction for 'she will'.
TOPIC: contractions
TYPE: TYPED
A: she'll
E: 'She will' becomes 'she'll' — the apostrophe replaces the letters 'wi'.
---
Q: Write the contraction for 'is not'.
TOPIC: contractions
TYPE: TYPED
A: isn't
E: 'Is not' becomes 'isn't' — the apostrophe replaces the missing letter 'o'.
---
Q: Write the contraction for 'we have'.
TOPIC: contractions
TYPE: TYPED
A: we've
E: 'We have' becomes 'we've' — the apostrophe replaces the letters 'ha'.
---
Q: Which word is the correct contraction for 'you are'?
TOPIC: contractions
O: your
O: you're
O: youre
O: you'r
A: 1
E: 'You're' is the contraction for 'you are'. 'Your' shows ownership and means something different.
---
Q: Which word is the correct contraction for 'it is'?
TOPIC: contractions
O: its
O: it's
O: its'
O: it is'nt
A: 1
E: 'It's' is the contraction for 'it is'. 'Its' (no apostrophe) shows ownership instead.
---
Q: What two words make up the contraction 'didn't'?
TOPIC: contractions
TYPE: TYPED
A: did not
E: 'Didn't' is short for 'did not' — the apostrophe replaces the missing 'o'.
---
Q: Which sentence shows the dog owns the bone correctly?
TOPIC: possessives
O: The dogs bone is buried.
O: The dog's bone is buried.
O: The dog' bone is buried.
O: The dogs' bone is buried.
A: 1
E: Add 's to a singular noun to show ownership: the dog's bone.
---
Q: Which sentence correctly shows Mia owns the backpack?
TOPIC: possessives
O: Mia's backpack is red.
O: Mias backpack is red.
O: Mia backpack's is red.
O: Mias' backpack is red.
A: 0
E: Add 's to a name to show ownership: Mia's backpack.
---
Q: Which sentence correctly shows more than one cat's toys?
TOPIC: possessives
O: The cats toys are everywhere.
O: The cat's toys are everywhere.
O: The cats' toys are everywhere.
O: The cats's toys are everywhere.
A: 2
E: For a plural noun already ending in s, just add an apostrophe after the s: cats' toys.
---
Q: Which sentence correctly shows the teacher owns the desk?
TOPIC: possessives
O: The teachers desk is clean.
O: The teacher's desk is clean.
O: The teachers' desk is clean.
O: The teacher' desk is clean.
A: 1
E: Add 's to a singular noun to show ownership: the teacher's desk.
---
Q: Which word correctly shows the children (plural) own the toys?
TOPIC: possessives
O: childrens'
O: children's
O: childrens
O: childre's
A: 1
E: 'Children' is already plural, so just add 's: the children's toys.
---
Q: Which sentence uses an apostrophe correctly?
TOPIC: possessives
O: The birds nest fell.
O: The bird's nest fell.
O: The birds' nest fell
O: The bird nest's fell.
A: 1
E: Add 's to a singular noun (one bird) to show ownership: the bird's nest.
---
Q: Which sentence uses quotation marks correctly?
TOPIC: quotes
O: She said, "I am hungry."
O: She said "I am hungry."
O: She said, I am hungry.
O: "She said, I am hungry."
A: 0
E: Quotation marks go around the exact words a person says: "I am hungry."
---
Q: Which sentence uses quotation marks correctly?
TOPIC: quotes
O: Tom yelled "Watch out!"
O: Tom yelled, "Watch out!"
O: Tom yelled, Watch out!
O: "Tom yelled, Watch out!"
A: 1
E: A comma comes before the quotation, and the exact spoken words go inside the quotation marks.
---
Q: Which sentence correctly shows what the dog's owner said?
TOPIC: quotes
O: The owner said, "Sit down."
O: The owner said "Sit down."
O: The owner said, Sit down.
O: The owner said Sit down.
A: 0
E: Use a comma after "said" and put the exact words inside quotation marks.
---
Q: Where do quotation marks belong in: '_I love this book,_ said Ana.'
TOPIC: quotes
O: Around the whole sentence
O: Around 'I love this book'
O: Around 'said Ana'
O: No quotation marks needed
A: 1
E: Quotation marks go around the exact words the person spoke: 'I love this book'.
---
Q: Which sentence uses quotation marks correctly?
TOPIC: quotes
O: "Let's go!" shouted Ben.
O: Let's go! shouted Ben.
O: "Let's go, shouted Ben."
O: Let's go, "shouted Ben."
A: 0
E: The spoken words come first inside quotation marks: "Let's go!" shouted Ben.
---
Q: What is the purpose of quotation marks?
TOPIC: quotes
O: To show a list of items
O: To show the exact words someone said
O: To end a sentence
O: To show ownership
A: 1
E: Quotation marks show the exact words a person says out loud.
---
Q: Which sentence uses capital letters correctly?
TOPIC: capitals
O: my dog Max loves to play.
O: My dog max loves to play.
O: My dog Max loves to play.
O: My Dog Max Loves To Play.
A: 2
E: Capitalize the first word of a sentence and the name of the dog, Max.
---
Q: Which sentence uses capital letters correctly?
TOPIC: capitals
O: we visited paris last summer.
O: We visited Paris last summer.
O: We visited paris Last summer.
O: we visited Paris last Summer.
A: 1
E: Capitalize the first word of the sentence and the proper noun 'Paris' (a place name).
---
Q: Which word should be capitalized in: 'our teacher, mr. lopez, is very kind.'
TOPIC: capitals
TYPE: TYPED
A: mr. lopez|mr.lopez|lopez
E: 'Mr. Lopez' is a proper noun (a person's title and name), so it should be capitalized.
---
Q: Which sentence uses capital letters correctly?
TOPIC: capitals
O: Monday is my favorite day.
O: monday is my favorite day.
O: Monday is my Favorite day.
O: monday is my Favorite Day.
A: 0
E: Days of the week, like Monday, are always capitalized.
---
Q: Which sentence uses capital letters correctly?
TOPIC: capitals
O: I live in texas.
O: I live in Texas.
O: i live in Texas.
O: I Live in texas.
A: 1
E: 'Texas' is a proper noun (a state name), so it must be capitalized, and sentences start with a capital letter.
---
Q: Which sentence uses capital letters correctly?
TOPIC: capitals
O: Our school trip is in October.
O: our school trip is in october.
O: Our School Trip is in October.
O: Our school trip is in october.
A: 0
E: Capitalize the first word of the sentence and the month name 'October'.
---
# ══════════════════════════════════════════════════════
# CATEGORY: Spelling
# ══════════════════════════════════════════════════════
CATEGORY: Spelling
Q: Which word is spelled correctly?
TOPIC: common-words
O: frend
O: freind
O: friend
O: friende
A: 2
E: 'Friend' is correct. Remember: 'fri-END' — a friend is with you until the end.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: becuase
O: because
O: becouse
O: becaus
A: 1
E: 'Because' is correct. A trick: 'Big Elephants Can Always Understand Small Elephants' spells it out.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: peaple
O: people
O: poeple
O: peple
A: 1
E: 'People' is correct. Remember it has 'eo' together: peo-ple.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: thier
O: their
O: theyre
O: theri
A: 1
E: 'Their' shows ownership, like 'their house'. Remember: T-H-E-I-R.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: wich
O: which
O: whitch
O: whichi
A: 1
E: 'Which' is correct. It starts with 'wh', just like 'what' and 'when'.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: diffrent
O: different
O: differant
O: diferent
A: 1
E: 'Different' is correct. Say it in parts: dif-fer-ent.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: favrite
O: favorite
O: favorate
O: favourit
A: 1
E: 'Favorite' is correct. Remember it has an 'o' after 'fav': fav-OR-ite.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: animle
O: animal
O: annimal
O: aminal
A: 1
E: 'Animal' is correct. It ends in -al, not -le.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: allways
O: always
O: alwais
O: alway
A: 1
E: 'Always' is correct — it's just one word: all + ways = always.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: beatiful
O: beautiful
O: beutiful
O: beautifull
A: 1
E: 'Beautiful' is correct. Remember the tricky order: b-e-a-u.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: togther
O: together
O: togeather
O: togethor
A: 1
E: 'Together' is correct. Break it into parts: to-geth-er.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: important
O: importent
O: importint
O: importan
A: 0
E: 'Important' is correct. It ends in -ant, not -ent.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: scool
O: school
O: shcool
O: schoool
A: 1
E: 'School' is correct. It starts with 'sch', which makes a 'sk' sound.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: famly
O: family
O: familly
O: familey
A: 1
E: 'Family' is correct — it has one M and one L.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: Wendsday
O: Wensday
O: Wednesday
O: Wendesday
A: 2
E: 'Wednesday' is correct — it has a silent 'd' after 'Wed'.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: Febuary
O: February
O: Feburary
O: Februray
A: 1
E: 'February' is correct — remember the tricky silent-feeling 'r' after 'Feb'.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: neighbor
O: nieghbor
O: neighbour
O: neigbor
A: 0
E: 'Neighbor' is correct in American English. Remember: 'ei' comes after 'gh'.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: surprize
O: suprise
O: surprise
O: suprize
A: 2
E: 'Surprise' is correct — it has an 'r' after 'su' and ends in -ise, not -ize.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: expect
O: expet
O: expekt
O: expext
A: 0
E: 'Expect' is correct — it ends in -ect.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: remembr
O: remember
O: remembar
O: rememeber
A: 1
E: 'Remember' is correct — break it into parts: re-mem-ber.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: tommorrow
O: tomorow
O: tomorrow
O: tommorow
A: 2
E: 'Tomorrow' is correct — one M, two Rs.
---
Q: Which word is spelled correctly?
TOPIC: common-words
O: speacial
O: special
O: specail
O: speciel
A: 1
E: 'Special' is correct — it ends in -cial.
---
Q: Add -s or -es correctly to make the regular plural: church + plural = ?
TOPIC: word-endings
TYPE: TYPED
A: churches
E: Words ending in -ch usually add -es: church -> churches.
---
Q: Add -s or -es correctly to make the regular plural: dish + plural = ?
TOPIC: word-endings
TYPE: TYPED
A: dishes
E: Words ending in -sh usually add -es: dish -> dishes.
---
Q: Add -s or -es correctly to make the regular plural: puppy + plural = ?
TOPIC: word-endings
TYPE: TYPED
A: puppies
E: Change y to i and add -es when a consonant comes before the y: puppy -> puppies.
---
Q: Add -s or -es correctly to make the regular plural: monkey + plural = ?
TOPIC: word-endings
TYPE: TYPED
A: monkeys
E: When a vowel comes before the y, just add -s: monkey -> monkeys.
---
Q: Add -ing correctly to the verb 'run'.
TOPIC: word-endings
TYPE: TYPED
A: running
E: Double the final consonant before adding -ing when a short verb ends in one vowel + one consonant: run -> running.
---
Q: Add -ing correctly to the verb 'make'.
TOPIC: word-endings
TYPE: TYPED
A: making
E: Drop the silent 'e' before adding -ing: make -> making.
---
Q: Add -ed correctly to the regular verb 'stop'.
TOPIC: word-endings
TYPE: TYPED
A: stopped
E: Double the final consonant before adding -ed for short verbs: stop -> stopped.
---
Q: Add -ed correctly to the regular verb 'hope'.
TOPIC: word-endings
TYPE: TYPED
A: hoped
E: Drop the silent 'e' before adding -ed: hope -> hoped.
---
Q: Add -ed correctly to the regular verb 'carry'.
TOPIC: word-endings
TYPE: TYPED
A: carried
E: Change y to i before adding -ed when a consonant comes before the y: carry -> carried.
---
Q: Add -s or -es correctly to make the regular plural: fox + plural = ?
TOPIC: word-endings
TYPE: TYPED
A: foxes
E: Words ending in -x usually add -es: fox -> foxes.
---
Q: Choose the correct word: I have ___ pencils in my bag.
TOPIC: homophones
O: to
O: too
O: two
O: tue
A: 2
E: 'Two' is the number 2. 'To' shows direction, and 'too' means 'also' or 'very'.
---
Q: Choose the correct word: Can I come ___?
TOPIC: homophones
O: to
O: too
O: two
O: tue
A: 1
E: 'Too' means 'also' here. 'To' shows direction, and 'two' is the number 2.
---
Q: Choose the correct word: ___ dog is barking loudly.
TOPIC: homophones
O: Their
O: There
O: They're
O: Thear
A: 0
E: 'Their' shows ownership (belongs to them). 'There' means a place, and 'they're' means 'they are'.
---
Q: Choose the correct word: ___ going to the park later.
TOPIC: homophones
O: Their
O: There
O: They're
O: Thear
A: 2
E: 'They're' is short for 'they are'. 'Their' shows ownership, and 'there' means a place.
---
Q: Choose the correct word: Put the book over ___.
TOPIC: homophones
O: their
O: there
O: they're
O: thair
A: 1
E: 'There' means a place or location. 'Their' shows ownership, and 'they're' means 'they are'.
---
Q: Choose the correct word: The cat licked ___ paw.
TOPIC: homophones
O: it's
O: its
O: its'
O: it is
A: 1
E: 'Its' (no apostrophe) shows ownership. 'It's' means 'it is'.
---
Q: Choose the correct word: ___ raining outside today.
TOPIC: homophones
O: Its
O: It's
O: Its'
O: Their
A: 1
E: 'It's' is short for 'it is'. 'Its' (no apostrophe) shows ownership.
---
Q: Choose the correct word: I can ___ the ocean from here.
TOPIC: homophones
O: sea
O: see
O: si
O: se
A: 1
E: 'See' means to look at something with your eyes. 'Sea' is a body of salt water.
---
Q: Choose the correct word: We swam in the ___.
TOPIC: homophones
O: sea
O: see
O: si
O: se
A: 0
E: 'Sea' is a large body of salt water. 'See' means to look at something.
---
Q: Choose the correct word: Please ___ down and rest.
TOPIC: homophones
O: sit
O: set
O: sat
O: site
A: 0
E: 'Sit' means to lower your body onto a seat. It's the correct word for this action.
---
Q: Which word is spelled correctly? (It has a silent 'k'.)
TOPIC: silent-letters
O: nife
O: knife
O: knif
O: nnife
A: 1
E: 'Knife' has a silent 'k' at the beginning.
---
Q: Which word is spelled correctly? (It has a silent 'w'.)
TOPIC: silent-letters
O: write
O: rite
O: rhite
O: wright
A: 0
E: 'Write' has a silent 'w' at the beginning, meaning to put words on paper.
---
Q: Which word is spelled correctly? (It has a silent 'b'.)
TOPIC: silent-letters
O: comb
O: com
O: combe
O: comm
A: 0
E: 'Comb' has a silent 'b' at the end — you use it to fix your hair.
---
Q: Which word is spelled correctly? (It has a silent 'h'.)
TOPIC: silent-letters
O: onest
O: honest
O: honnest
O: honist
A: 1
E: 'Honest' has a silent 'h' at the beginning.
---
Q: Which word is spelled correctly? (It has a silent 'g'.)
TOPIC: silent-letters
O: sign
O: sine
O: siegn
O: sighn
A: 0
E: 'Sign' has a silent 'g' — you sign your name on paper.
---
Q: Which word is spelled correctly? (It has a silent 'l'.)
TOPIC: silent-letters
O: hafe
O: half
O: haff
O: halph
A: 1
E: 'Half' has a silent 'l' — it means one of two equal parts.
---
Q: Which word is spelled correctly? (It has a silent 't'.)
TOPIC: silent-letters
O: lisen
O: listen
O: listin
O: lissen
A: 1
E: 'Listen' has a silent 't' — remember to use your ears when you listen.
---
Q: Which word is spelled correctly? (It has a silent 'e' that changes the vowel sound.)
TOPIC: silent-letters
O: cak
O: cake
O: caek
O: cayk
A: 1
E: 'Cake' ends in a silent 'e' that makes the 'a' say its name (long A sound).
---
Q: Combine 'sun' and 'flower' to make a compound word.
TOPIC: compound-words
TYPE: TYPED
A: sunflower
E: 'Sunflower' is a compound word made from 'sun' + 'flower'.
---
Q: Combine 'foot' and 'ball' to make a compound word.
TOPIC: compound-words
TYPE: TYPED
A: football
E: 'Football' is a compound word made from 'foot' + 'ball'.
---
Q: Combine 'butter' and 'fly' to make a compound word.
TOPIC: compound-words
TYPE: TYPED
A: butterfly
E: 'Butterfly' is a compound word made from 'butter' + 'fly'.
---
Q: Which two smaller words make up 'rainbow'?
TOPIC: compound-words
TYPE: TYPED
A: rain and bow|rain, bow|rain bow
E: 'Rainbow' is made from 'rain' + 'bow', the colorful arc you see after rain.
---
Q: Which two smaller words make up 'toothbrush'?
TOPIC: compound-words
TYPE: TYPED
A: tooth and brush|tooth, brush|tooth brush
E: 'Toothbrush' is made from 'tooth' + 'brush', the tool you use to brush your teeth.
---
Q: Combine 'back' and 'pack' to make a compound word.
TOPIC: compound-words
TYPE: TYPED
A: backpack
E: 'Backpack' is a compound word made from 'back' + 'pack'.
---
# ══════════════════════════════════════════════════════
# CATEGORY: Vocabulary
# ══════════════════════════════════════════════════════
CATEGORY: Vocabulary
Q: Which word means almost the same as 'happy'?
TOPIC: synonyms
O: angry
O: glad
O: tired
O: cold
A: 1
E: 'Glad' means almost the same as 'happy' — both describe a good feeling.
---
Q: Which word means almost the same as 'big'?
TOPIC: synonyms
O: large
O: tiny
O: small
O: short
A: 0
E: 'Large' means almost the same as 'big' — both describe something great in size.
---
Q: Which word means almost the same as 'fast'?
TOPIC: synonyms
O: slow
O: quick
O: lazy
O: late
A: 1
E: 'Quick' means almost the same as 'fast' — both describe high speed.
---
Q: Which word means almost the same as 'smart'?
TOPIC: synonyms
O: silly
O: clever
O: tired
O: shy
A: 1
E: 'Clever' means almost the same as 'smart' — both describe being good at thinking.
---
Q: Which word means almost the same as 'scared'?
TOPIC: synonyms
O: afraid
O: brave
O: calm
O: proud
A: 0
E: 'Afraid' means almost the same as 'scared' — both describe feeling fear.
---
Q: Which word means almost the same as 'pretty'?
TOPIC: synonyms
O: ugly
O: plain
O: beautiful
O: messy
A: 2
E: 'Beautiful' means almost the same as 'pretty' — both describe something nice to look at.
---
Q: Which word means almost the same as 'small'?
TOPIC: synonyms
O: huge
O: tiny
O: tall
O: wide
A: 1
E: 'Tiny' means almost the same as 'small' — both describe something not big.
---
Q: Which word means almost the same as 'begin'?
TOPIC: synonyms
O: end
O: stop
O: start
O: finish
A: 2
E: 'Start' means almost the same as 'begin' — both mean to get something going.
---
Q: Which word means almost the same as 'loud'?
TOPIC: synonyms
O: quiet
O: noisy
O: soft
O: gentle
A: 1
E: 'Noisy' means almost the same as 'loud' — both describe a lot of sound.
---
Q: Which word means almost the same as 'sad'?
TOPIC: synonyms
O: joyful
O: unhappy
O: excited
O: proud
A: 1
E: 'Unhappy' means almost the same as 'sad' — both describe feeling down.
---
Q: Which word means almost the same as 'tired'?
TOPIC: synonyms
O: sleepy
O: awake
O: energetic
O: excited
A: 0
E: 'Sleepy' means almost the same as 'tired' — both describe needing rest.
---
Q: Which word means almost the same as 'funny'?
TOPIC: synonyms
O: silly
O: serious
O: boring
O: sad
A: 0
E: 'Silly' means almost the same as 'funny' — both describe something that makes you laugh.
---
Q: Which word means the opposite of 'up'?
TOPIC: antonyms
O: down
O: high
O: top
O: over
A: 0
E: 'Down' is the opposite of 'up' — they show opposite directions.
---
Q: Which word means the opposite of 'hot'?
TOPIC: antonyms
O: warm
O: cold
O: sunny
O: dry
A: 1
E: 'Cold' is the opposite of 'hot' — they describe opposite temperatures.
---
Q: Which word means the opposite of 'happy'?
TOPIC: antonyms
O: glad
O: sad
O: cheerful
O: joyful
A: 1
E: 'Sad' is the opposite of 'happy' — they describe opposite feelings.
---
Q: Which word means the opposite of 'fast'?
TOPIC: antonyms
O: quick
O: speedy
O: slow
O: rapid
A: 2
E: 'Slow' is the opposite of 'fast' — they describe opposite speeds.
---
Q: Which word means the opposite of 'open'?
TOPIC: antonyms
O: closed
O: wide
O: ready
O: empty
A: 0
E: 'Closed' is the opposite of 'open'.
---
Q: Which word means the opposite of 'full'?
TOPIC: antonyms
O: large
O: empty
O: heavy
O: tall
A: 1
E: 'Empty' is the opposite of 'full' — one has nothing inside, the other has a lot.
---
Q: Which word means the opposite of 'wet'?
TOPIC: antonyms
O: dry
O: cold
O: clean
O: soft
A: 0
E: 'Dry' is the opposite of 'wet'.
---
Q: Which word means the opposite of 'young'?
TOPIC: antonyms
O: small
O: new
O: old
O: tiny
A: 2
E: 'Old' is the opposite of 'young' — they describe opposite ages.
---
Q: Which word means the opposite of 'easy'?
TOPIC: antonyms
O: simple
O: hard
O: quick
O: light
A: 1
E: 'Hard' is the opposite of 'easy' — one is difficult, the other is simple.
---
Q: Which word means the opposite of 'begin'?
TOPIC: antonyms
O: start
O: open
O: end
O: continue
A: 2
E: 'End' is the opposite of 'begin' — one starts something, the other finishes it.
---
Q: Which word means the opposite of 'light' (not heavy)?
TOPIC: antonyms
O: bright
O: heavy
O: dark
O: soft
A: 1
E: 'Heavy' is the opposite of 'light' when talking about weight.
---
Q: Which word means the opposite of 'day'?
TOPIC: antonyms
O: morning
O: night
O: noon
O: sunset
A: 1
E: 'Night' is the opposite of 'day'.
---
Q: What does the prefix 're-' mean in 'redo'?
TOPIC: prefixes-suffixes
O: not
O: again
O: before
O: under
A: 1
E: The prefix 're-' means 'again'. 'Redo' means to do something again.
---
Q: What does the prefix 'un-' mean in 'unhappy'?
TOPIC: prefixes-suffixes
O: very
O: not
O: again
O: before
A: 1
E: The prefix 'un-' means 'not'. 'Unhappy' means not happy.
---
Q: What does the suffix '-ful' mean in 'joyful'?
TOPIC: prefixes-suffixes
O: without
O: full of
O: before
O: against
A: 1
E: The suffix '-ful' means 'full of'. 'Joyful' means full of joy.
---
Q: What does the suffix '-less' mean in 'careless'?
TOPIC: prefixes-suffixes
O: full of
O: without
O: again
O: very
A: 1
E: The suffix '-less' means 'without'. 'Careless' means without care.
---
Q: What does the prefix 'pre-' mean in 'preview'?
TOPIC: prefixes-suffixes
O: after
O: before
O: never
O: under
A: 1
E: The prefix 'pre-' means 'before'. 'Preview' means to view before.
---
Q: What does the suffix '-er' add to the word 'teach' in 'teacher'?
TOPIC: prefixes-suffixes
O: a small amount
O: a person who does the action
O: the opposite meaning
O: a place
A: 1
E: The suffix '-er' often means 'a person who does the action'. A teacher is a person who teaches.
---
Q: What does the prefix 'dis-' mean in 'disagree'?
TOPIC: prefixes-suffixes
O: again
O: not / opposite of
O: before
O: full of
A: 1
E: The prefix 'dis-' means 'not' or the opposite of. 'Disagree' means to not agree.
---
Q: What does the suffix '-est' show in 'biggest'?
TOPIC: prefixes-suffixes
O: comparing two things
O: comparing more than two things
O: the opposite meaning
O: full of something
A: 1
E: The suffix '-est' shows the most of something when comparing more than two things. 'Biggest' means the most big.
---
Q: What does the suffix '-able' mean in 'washable'?
TOPIC: prefixes-suffixes
O: can be done
O: cannot be done
O: full of
O: without
A: 0
E: The suffix '-able' means 'can be done'. 'Washable' means it can be washed.
---
Q: What does the prefix 'mis-' mean in 'misspell'?
TOPIC: prefixes-suffixes
O: correctly
O: wrongly
O: again
O: before
A: 1
E: The prefix 'mis-' means 'wrongly'. 'Misspell' means to spell wrongly.
---
Q: 'The puppy was tiny, so it could fit in my hand.' What does 'tiny' mean?
TOPIC: context-clues
O: very big
O: very small
O: very loud
O: very fast
A: 1
E: 'Tiny' means very small, which is why the puppy could fit in a hand.
---
Q: 'The ice was slippery, so Jake slid across the pond.' What does 'slippery' mean?
TOPIC: context-clues
O: rough and sticky
O: smooth and hard to grip
O: warm and soft
O: dry and dusty
A: 1
E: 'Slippery' means smooth and hard to grip, which is why Jake slid.
---
Q: 'The soup was so spicy that Mia's mouth felt hot.' What does 'spicy' mean?
TOPIC: context-clues
O: having a strong, hot taste
O: having no taste at all
O: being very cold
O: being very sweet
A: 0
E: 'Spicy' describes food with a strong, hot taste.
---
Q: 'The ancient castle was hundreds of years old.' What does 'ancient' mean?
TOPIC: context-clues
O: brand new
O: very old
O: very small
O: very colorful
A: 1
E: 'Ancient' means very old, from a long time ago.
---
Q: 'Ben was exhausted after running the whole race.' What does 'exhausted' mean?
TOPIC: context-clues
O: very excited
O: very hungry
O: very tired
O: very happy
A: 2
E: 'Exhausted' means very tired, often after hard work or exercise.
---
Q: 'The giant redwood tree was enormous.' What does 'enormous' mean?
TOPIC: context-clues
O: very small
O: very green
O: extremely large
O: extremely old
A: 2
E: 'Enormous' means extremely large in size.
---
Q: 'She whispered so no one else could hear.' What does 'whispered' mean?
TOPIC: context-clues
O: shouted loudly
O: spoke very softly
O: sang a song
O: laughed quietly
A: 1
E: 'Whispered' means spoke very softly and quietly.
---
Q: 'The kitten was curious about the box and kept sniffing it.' What does 'curious' mean?
TOPIC: context-clues
O: scared of something
O: wanting to know more about something
O: tired of something
O: angry about something
A: 1
E: 'Curious' means wanting to know or learn more about something.
---
Q: 'The trail was so narrow that only one person could walk at a time.' What does 'narrow' mean?
TOPIC: context-clues
O: very wide
O: not wide, thin
O: very long
O: very steep
A: 1
E: 'Narrow' means not wide — thin from side to side.
---
Q: 'After the storm, the sky was calm and still.' What does 'calm' mean?
TOPIC: context-clues
O: peaceful and quiet
O: loud and stormy
O: bright and sunny
O: cold and windy
A: 0
E: 'Calm' means peaceful and quiet, without much movement or noise.
---
Q: What does 'birdhouse' mean?
TOPIC: word-meanings
O: a house shaped like a bird
O: a small house made for birds to live in
O: a bird that lives in a house
O: a house with no roof
A: 1
E: A 'birdhouse' is a small house made for birds to live or nest in.
---
Q: What does 'raincoat' mean?
TOPIC: word-meanings
O: a coat made of rain
O: a coat you wear to stay dry in the rain
O: a coat only worn in winter
O: a coat with no sleeves
A: 1
E: A 'raincoat' is a coat you wear to stay dry when it rains.
---
Q: What does 'notebook' mean?
TOPIC: word-meanings
O: a book with no pages
O: a book used for writing notes
O: a book that makes noise
O: a very old book
A: 1
E: A 'notebook' is a book used for writing notes.
---
Q: What does 'snowman' mean?
TOPIC: word-meanings
O: a man who studies snow
O: a figure made out of snow
O: a very cold man
O: a man who shovels snow for a job
A: 1
E: A 'snowman' is a figure people build out of packed snow.
---
Q: What does 'lighthouse' mean?
TOPIC: word-meanings
O: a house made of light
O: a tall tower with a light to guide ships
O: a very bright house
O: a house with many windows
A: 1
E: A 'lighthouse' is a tall tower with a light that helps guide ships safely.
---
Q: What does 'sidewalk' mean?
TOPIC: word-meanings
O: walking sideways
O: a path next to a street for walking
O: the side of a car
O: a walk in the woods
A: 1
E: A 'sidewalk' is a paved path next to a street where people walk.
---
Q: Which word does NOT belong with the others: apple, banana, carrot, orange?
TOPIC: word-groups
O: apple
O: banana
O: carrot
O: orange
A: 2
E: 'Carrot' is a vegetable, while apple, banana, and orange are all fruits.
---
Q: Which word does NOT belong with the others: dog, cat, chair, rabbit?
TOPIC: word-groups
O: dog
O: cat
O: chair
O: rabbit
A: 2
E: 'Chair' is furniture, while dog, cat, and rabbit are all animals.
---
Q: Which word does NOT belong with the others: red, blue, happy, green?
TOPIC: word-groups
O: red
O: blue
O: happy
O: green
A: 2
E: 'Happy' is a feeling, while red, blue, and green are all colors.
---
Q: Which word does NOT belong with the others: run, jump, swim, table?
TOPIC: word-groups
O: run
O: jump
O: swim
O: table
A: 3
E: 'Table' is a thing (a noun), while run, jump, and swim are all actions (verbs).
---
Q: Which word IS a type of weather: sunny, hungry, funny, angry?
TOPIC: word-groups
O: sunny
O: hungry
O: funny
O: angry
A: 0
E: 'Sunny' describes weather. The other words describe feelings.
---
Q: Which word IS a season: summer, Monday, October, morning?
TOPIC: word-groups
O: summer
O: Monday
O: October
O: morning
A: 0
E: 'Summer' is a season. Monday is a day, October is a month, and morning is a time of day.
---
Q: Which word does NOT belong with the others: whale, dolphin, shark, eagle?
TOPIC: word-groups
O: whale
O: dolphin
O: shark
O: eagle
A: 3
E: 'Eagle' is a bird that flies in the sky, while whale, dolphin, and shark are all animals that live in the ocean.
---
Q: Which word means a group of the same kind, like 'a pack of wolves'?
TOPIC: word-groups
O: collective noun
O: adjective
O: pronoun
O: verb
A: 0
E: A word like 'pack' that names a group of things is called a collective noun.
---
# ══════════════════════════════════════════════════════
# CATEGORY: Reading Comprehension
# ══════════════════════════════════════════════════════
CATEGORY: Reading Comprehension
Q: Read: 'Dogs need food, water, exercise, and love to stay healthy and happy.' What is the main idea?
TOPIC: main-idea
O: Dogs only need food.
O: Dogs need several things to be healthy and happy.
O: Dogs do not need water.
O: Dogs are unhappy animals.
A: 1
E: The main idea sums up the whole passage: dogs need several things (food, water, exercise, love) to stay healthy and happy.
---
Q: Read: 'Bees fly from flower to flower. They collect pollen and make honey. Bees also help plants grow by spreading pollen.' What is the main idea?
TOPIC: main-idea
O: Bees are dangerous insects.
O: Bees only eat honey.
O: Bees collect pollen and help plants grow.
O: Bees cannot fly far.
A: 2
E: The main idea is the big-picture message: bees collect pollen and help plants grow.
---
Q: Read: 'Recycling helps the earth. It saves trees, saves energy, and reduces trash in landfills.' What is the main idea?
TOPIC: main-idea
O: Recycling is difficult to do.
O: Recycling helps the earth in several ways.
O: Recycling wastes energy.
O: Recycling is only for paper.
A: 1
E: The main idea is that recycling helps the earth, and the sentence gives several reasons why.
---
Q: Read: 'Sharks have been swimming in the ocean for millions of years. They come in many shapes and sizes. Some are as small as a hand, while others are longer than a bus.' What is this passage mostly about?
TOPIC: main-idea
O: How sharks hunt for food
O: The many shapes, sizes, and long history of sharks
O: Why sharks are dangerous
O: How to swim safely near sharks
A: 1
E: The passage focuses on how long sharks have existed and how much their shapes and sizes vary.
---
Q: Read: 'Plants need sunlight, water, and soil to grow. Without these three things, a plant cannot survive.' What is the main idea?
TOPIC: main-idea
O: Plants need sunlight, water, and soil to grow.
O: Plants can grow without water.
O: Plants only need sunlight.
O: Plants grow best in the dark.
A: 0
E: The main idea is stated directly: plants need sunlight, water, and soil to grow.
---
Q: Read: 'Libraries are useful places. You can borrow books, use computers, and attend fun events for free.' What is the main idea?
TOPIC: main-idea
O: Libraries only have books.
O: Libraries cost a lot of money.
O: Libraries offer many useful and free resources.
O: Libraries are closed on weekends.
A: 2
E: The main idea is that libraries offer many useful things, and all of them are free.
---
Q: Read: 'Volcanoes form when melted rock, called magma, pushes up through cracks in the earth. When it reaches the surface, it is called lava.' What is this passage mostly about?
TOPIC: main-idea
O: How volcanoes form
O: Why the ocean is salty
O: How mountains are climbed
O: Why the sky is blue
A: 0
E: The passage explains how volcanoes form using magma and lava.
---
Q: Read: 'Maya studied every night for two weeks. On the day of the test, she felt calm and ready.' What can we infer about Maya?
TOPIC: inference
O: She did not study at all.
O: Her hard work made her feel confident.
O: She was scared of the test.
O: She forgot about the test.
A: 1
E: We can infer that Maya's studying made her feel confident and calm on test day.
---
Q: Read: 'Liam forgot his umbrella and had to run home in the pouring rain. He arrived soaking wet and sneezing.' What is Liam most likely to catch?
TOPIC: inference
TYPE: TYPED
A: a cold|cold|the flu|flu
E: Since Liam got soaked in the rain and started sneezing, he is likely to catch a cold.
---
Q: Read: 'Sara's hands were shaking as she walked up to the stage to give her speech.' How does Sara likely feel?
TOPIC: inference
O: Excited and calm
O: Nervous
O: Angry
O: Bored
A: 1
E: Shaking hands are a clue that Sara feels nervous before her speech.
---
Q: Read: 'Jake slammed the door and threw his backpack on the floor.' How does Jake likely feel?
TOPIC: inference
O: Happy
O: Sleepy
O: Frustrated or angry
O: Relaxed
A: 2
E: Slamming the door and throwing his backpack are clues that Jake feels frustrated or angry.
---
Q: Read: 'Emma jumped up and down, clapping her hands, when she saw the puppy under the tree.' How does Emma likely feel?
TOPIC: inference
O: Excited
O: Sad
O: Tired
O: Worried
A: 0
E: Jumping and clapping are clues that Emma feels excited.
---
Q: Read: 'The kids searched the whole yard, calling their cat's name, but she was nowhere to be found.' How do the kids likely feel?
TOPIC: inference
O: Proud
O: Worried
O: Excited
O: Bored
A: 1
E: Not being able to find their cat would make the kids feel worried.
---
Q: Read: 'Noah practiced his violin for one hour every day for a year. At the concert, he played perfectly.' What word best describes Noah's daily practice?
TOPIC: inference
TYPE: TYPED
A: dedication|dedicated|discipline|disciplined|commitment|persistence|consistent
E: Practicing every day for a year without missing shows dedication and discipline.
---
Q: Read: 'Mia shared her lunch with a classmate who had forgotten his.' What can we infer about Mia?
TOPIC: inference
O: She is a kind and generous person.
O: She dislikes her classmate.
O: She forgot her own lunch.
O: She was hungry all day.
A: 0
E: Sharing her lunch with someone in need shows that Mia is kind and generous.
---
Q: Read: 'It rained all night, so the soccer game was canceled.' What was the CAUSE?
TOPIC: cause-effect
O: The soccer game was canceled.
O: It rained all night.
O: The players were tired.
O: The field was empty.
A: 1
E: The cause is what happened first and made something else happen: it rained all night, which caused the game to be canceled.
---
Q: Read: 'Ben did not study, so he failed the quiz.' What was the EFFECT?
TOPIC: cause-effect
O: Ben did not study.
O: Ben failed the quiz.
O: Ben liked the quiz.
O: Ben studied hard.
A: 1
E: The effect is the result: because Ben did not study, he failed the quiz.
---
Q: Read: 'The ice cream melted because it was left in the sun.' What was the CAUSE?
TOPIC: cause-effect
O: The ice cream melted.
O: It was left in the sun.
O: The ice cream was sweet.
O: It was put in the freezer.
A: 1
E: The cause is what made the melting happen: being left in the sun.
---
Q: Read: 'Because Leo watered the plant every day, it grew tall and healthy.' What was the EFFECT?
TOPIC: cause-effect
O: Leo watered the plant.
O: The plant grew tall and healthy.
O: The plant was small.
O: Leo forgot to water it.
A: 1
E: The effect is the result of the watering: the plant grew tall and healthy.
---
Q: Read: 'The road was icy, so cars had to drive slowly.' What was the CAUSE?
TOPIC: cause-effect
O: Cars drove slowly.
O: The road was icy.
O: It was a sunny day.
O: The cars were new.
A: 1
E: The cause is what made drivers slow down: the icy road.
---
Q: Read: 'Anna forgot her lunch, so she felt very hungry by noon.' What was the EFFECT?
TOPIC: cause-effect
O: Anna forgot her lunch.
O: Anna felt very hungry.
O: Anna ate breakfast.
O: Anna packed extra food.
A: 1
E: The effect is what happened as a result: Anna felt very hungry.
---
Q: Read: 'The balloon popped because it was filled with too much air.' What was the CAUSE?
TOPIC: cause-effect
O: The balloon popped.
O: It was filled with too much air.
O: The balloon was red.
O: It floated away.
A: 1
E: The cause is what led to the balloon popping: too much air.
---
Q: Read: 'First, mix the flour and sugar. Next, add the eggs. Finally, bake for 20 minutes.' What happens first?
TOPIC: sequence
O: Bake for 20 minutes
O: Mix the flour and sugar
O: Add the eggs
O: Take it out of the oven
A: 1
E: The word 'first' tells us the first step is mixing the flour and sugar.
---
Q: Read: 'After school, Kai did his homework. Then he ate a snack. Finally, he played outside.' What did Kai do LAST?
TOPIC: sequence
O: Homework
O: Eat a snack
O: Play outside
O: Go to school
A: 2
E: The word 'finally' tells us the last thing Kai did was play outside.
---
Q: Read: 'Before you cross the street, look both ways. Then walk quickly across.' What should you do FIRST?
TOPIC: sequence
O: Walk quickly across
O: Look both ways
O: Run across
O: Stop in the middle
A: 1
E: The word 'before' tells us that looking both ways happens first.
---
Q: Which word signals that something happens LAST in a set of instructions?
TOPIC: sequence
O: First
O: Next
O: Then
O: Finally
A: 3
E: 'Finally' signals the last step in a sequence of events or instructions.
---
Q: Read: 'The caterpillar first eats leaves, then forms a cocoon, and finally becomes a butterfly.' What happens in the MIDDLE?
TOPIC: sequence
O: Eating leaves
O: Forming a cocoon
O: Becoming a butterfly
O: Laying eggs
A: 1
E: The word 'then' points to the middle step: forming a cocoon.
---
Q: Which word signals that something happens FIRST?
TOPIC: sequence
O: Finally
O: After
O: First
O: Later
A: 2
E: 'First' signals the beginning of a sequence of events.
---
Q: What is the 'setting' of a story?
TOPIC: story-elements
O: The problem the characters face
O: The time and place where the story happens
O: The lesson the story teaches
O: The list of events in order
A: 1
E: The setting is the time and place in which a story takes place.
---
Q: What is a 'character' in a story?
TOPIC: story-elements
O: The place where the story happens
O: A person or animal in the story
O: The main problem in the story
O: The last sentence of the story
A: 1
E: A character is a person, animal, or creature that takes part in the story.
---
Q: Read: 'Lily was scared to go down the big slide, but her friends cheered her on. She finally went down and smiled with pride.' What is the PROBLEM in this story?
TOPIC: story-elements
O: Lily's friends left her.
O: Lily was scared to go down the slide.
O: Lily broke the slide.
O: Lily fell asleep.
A: 1
E: The problem is the challenge the character faces: Lily was scared to go down the slide.
---
Q: Read: 'Lily was scared to go down the big slide, but her friends cheered her on. She finally went down and smiled with pride.' How is the problem SOLVED?
TOPIC: story-elements
O: Lily gives up and goes home.
O: Lily's friends encourage her, and she goes down the slide.
O: Lily builds a new slide.
O: Lily never tries again.
A: 1
E: The problem is solved when Lily's friends cheer her on and she bravely goes down the slide.
---
Q: What is the 'plot' of a story?
TOPIC: story-elements
O: The place where the story happens
O: The order of events that make up the story
O: A single character's name
O: The cover of the book
A: 1
E: The plot is the sequence of events that make up the story from beginning to end.
---
Q: What is a 'theme' in a story?
TOPIC: story-elements
O: The time and place where the story happens
O: The list of characters in the story
O: The central lesson or message the story teaches
O: The problem the main character faces
A: 2
E: The theme is the central message or lesson that the story conveys, such as 'kindness is rewarded.'
---
Q: Read: 'Her smile was as bright as the sun.' What literary device compares two things using 'as'?
TOPIC: figurative-language
TYPE: TYPED
A: simile
E: A simile compares two unlike things using 'like' or 'as'. Here, her smile is compared to the sun.
---
Q: Read: 'The wind howled through the trees.' What literary device gives the wind a human quality?
TOPIC: figurative-language
TYPE: TYPED
A: personification
E: Personification gives human qualities (like howling) to something that is not human, like the wind.
---
Q: Read: 'The classroom was as quiet as a library.' What literary device is used?
TOPIC: figurative-language
O: Simile
O: Personification
O: Rhyme
O: Alliteration
A: 0
E: This is a simile — it compares the classroom to a library using the word 'as'.
---
Q: Read: 'Slippery snakes slithered silently.' What literary device repeats the same beginning sound?
TOPIC: figurative-language
O: Simile
O: Metaphor
O: Alliteration
O: Personification
A: 2
E: Alliteration repeats the same beginning sound in nearby words, like the 's' sound here.
---
Q: Read: 'The stars danced across the night sky.' What literary device gives the stars a human action?
TOPIC: figurative-language
O: Simile
O: Personification
O: Rhyme
O: Onomatopoeia
A: 1
E: Personification gives human actions, like dancing, to something that is not human, like stars.
---
Q: Which word below is an example of onomatopoeia (a word that sounds like the noise it makes)?
TOPIC: figurative-language
O: Buzz
O: Happy
O: Quickly
O: Bright
A: 0
E: 'Buzz' is onomatopoeia because it sounds like the noise a bee makes.
---
Q: Which sentence is a FACT?
TOPIC: fact-opinion
O: Ice cream is the best dessert.
O: The sun rises in the east.
O: Blue is the prettiest color.
O: Summer is more fun than winter.
A: 1
E: A fact can be proven true. 'The sun rises in the east' can be checked and proven.
---
Q: Which sentence is an OPINION?
TOPIC: fact-opinion
O: Water freezes at 32 degrees Fahrenheit.
O: Dogs are better pets than cats.
O: A triangle has three sides.
O: There are seven days in a week.
A: 1
E: An opinion is what someone believes or feels, and it cannot be proven true or false. 'Dogs are better pets than cats' is a personal opinion.
---
Q: Which sentence is a FACT?
TOPIC: fact-opinion
O: Pizza tastes amazing.
O: A week has seven days.
O: Winter is the best season.
O: Cats are cuter than dogs.
A: 1
E: 'A week has seven days' can be proven true, so it is a fact.
---
Q: Which sentence is an OPINION?
TOPIC: fact-opinion
O: The moon orbits the earth.
O: Roses are red flowers.
O: Chocolate ice cream is the tastiest flavor.
O: A year has twelve months.
A: 2
E: 'Chocolate ice cream is the tastiest flavor' is a personal opinion, not a provable fact.
---
Q: What does it mean to 'summarize' a story?
TOPIC: summarizing
O: Copy the story word for word
O: Tell only the most important parts in your own words
O: Add your own made-up ending
O: List every single detail
A: 1
E: To summarize means to briefly retell the most important parts of a story in your own words.
---
Q: Which is the BEST summary of: 'Tom planted a seed. He watered it every day. After two weeks, a small plant grew.'
TOPIC: summarizing
O: Tom likes gardening tools.
O: Tom planted and cared for a seed until it grew into a plant.
O: Tom bought a plant from a store.
O: Tom forgot to water his seed.
A: 1
E: A good summary captures the main events briefly: Tom planted and cared for a seed until it became a plant.
---
Q: Which is the BEST summary of: 'Mia lost her kite in the wind. She looked everywhere. At last, she found it stuck in a tree.'
TOPIC: summarizing
O: Mia likes windy days.
O: Mia lost her kite, searched for it, and found it in a tree.
O: Mia bought a brand new kite.
O: Mia climbed a tree for fun.
A: 1
E: A good summary tells the most important events in order: Mia lost her kite, searched, and found it in a tree.
---
Q: Which is the BEST summary of: 'A frog starts as an egg. Then it becomes a tadpole with a tail. Finally, it grows legs and becomes a frog.'
TOPIC: summarizing
O: A frog changes from an egg to a tadpole to a frog.
O: Frogs live in ponds.
O: Tadpoles have long tails.
O: Frogs are green.
A: 0
E: A summary covers the whole passage in a few words. Only the first choice tells all the stages of change.
---
Q: A good summary should be...
TOPIC: summarizing
O: as long as the story
O: short and tell only the main points
O: full of your own opinions
O: a list of every tiny detail
A: 1
E: A summary is short. It tells only the most important parts of the story.
---
Q: Which is the BEST summary of: 'Max taught his puppy to sit, stay, and roll over. After weeks of practice, the puppy knew every trick.'
TOPIC: summarizing
O: Max has a puppy.
O: Max practiced with his puppy until it learned all the tricks.
O: Puppies like to roll over.
O: Max went to the park.
A: 1
E: The best summary covers the main events: Max practiced, and the puppy learned the tricks.
---
Q: Which sentence is a FACT?
TOPIC: fact-opinion
O: Spiders are scary.
O: Spiders have eight legs.
O: Spiders are the ugliest bugs.
O: Everyone should love spiders.
A: 1
E: A fact can be proven true. You can count a spider's legs, so it is a fact.
---
Q: Which sentence is an OPINION?
TOPIC: fact-opinion
O: A soccer ball is round.
O: Soccer is played with a ball.
O: Soccer is the most exciting sport.
O: A soccer team has players.
A: 2
E: 'The most exciting' is what someone thinks or feels, so it is an opinion. It cannot be proven.
---
`;
