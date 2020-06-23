# A more detailed Sourceml presentation

[back to README](../README.md)

## Where does Sourceml come from ?

Sourceml history is starting around 2010, and is motivated by free music
concerns. All this Sourceml thing, here, is about publishing music with
free licences like the
[Creative Commons](https://creativecommons.org/)
ones, or the
[Licence Art Libre](https://artlibre.org/)
(and many others).

Sourceml is also a tool for music publishing that can completely ignore those
licences. But Sourceml is, in the first place, about free music and how (in
short) free music can be shared when you plan for exemple on making it available
for remixes and other derivative works.

This is one of the two main ideas behind Sourceml. The other one is about
making a publishing tool available for anyone who wishes to set a personnal
music website, or a plateform with multiple autors.

## Sharing music with separated tracks

It's one thing to use a licence that grants everyone the right to publish
derivative works. But it's another one to actually do a derivative work from
a single audio file. This can surely be done but the playground becomes much
more interesting when separated tracks are available, like one with the drums,
an other one for the voices and so on.

Sourceml can organize songs in albums, but also tracks in songs and let you
publish music that can be more easily reused.

## Sharing music informations

Sourceml makes available, for each published *source* (an album, a song or a
track) an XML description containing informations like the title, the author,
the licence... Each *source* published on a Sourceml installation has an URL
where you can find this source's informations in an XML format.

Those XML files are used by Sourceml to establish relations between sources.

Let's say you have done a derivative work from a song that was published on your
Sourceml installation, or on any other Sourceml installation, and you want to
credit the original song. Copy the song's XML URL (available via a link on each
source), then paste it on your song edition page, in a dedicated field. That
way, Sourceml will read the song's informations and your derivative work will be
published with a credit and a link to the original song.

You can also make simple references, for exemple if you used a track from another
song, you can *add* this track to your song's tracks, by telling Sourceml the
original track's XML URL. Your song will be available with, amoung its tracks,
the one you referenced, with the original title, author and licence informations.
