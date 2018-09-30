#!/usr/bin/python
# coding: utf-8

import sys, hashlib
import sys

try:
    userFile = sys.argv[1]
    fUser = open(userFile)

except IndexError:
    sys.exit(1)

except IOError:
    print "Nem létezik a fájl: %s" % userFile

def Exit(msg = ''):
    print 'Hiba: ', msg
    sys.exit(1)

def sha(pw):
    return hashlib.sha256(pw.encode('utf-8')).hexdigest()

fUser = open(userFile)

Insert = "INSERT INTO %s (id, jelszo, %s) VALUES (%s, '%s', '%s');"

INSERT, OUT = [], []

List = fUser.read().split('===\n')

# Tanárok felsorolása
listTanar = {}
for sor in List[0].split('\n'):
    if len(sor) == 0 or sor[0] == '#': continue
    nev, uid, jelszo = sor.split(';')
    if listTanar.has_key(uid): Exit('Dupla tanár azonosító: %s' % uid)

    listTanar[uid] = nev
    #jelszo = gen_password()

    OUT.append('%s;%s' % (nev, jelszo))
    INSERT.append(Insert % ('Tanar', 'tnev', uid, sha(jelszo), nev))

OUT.append('===')
INSERT.append('')

# Osztályok felsorolása
listOsztaly = {}
for sor in List[1].split('\n'):
    if len(sor) == 0 or sor[0] == '#': continue
    oszt, onev, ofo = sor.split(';')
    if listOsztaly.has_key(oszt): Exit('Dupla osztály azonosító: %s' % oszt)
    if not listTanar.has_key(ofo): Exit('%s osztály főnöke (%s) nem szerepel a tanárok közt!' % (oszt, ofo))
    listOsztaly[oszt] = onev

    INSERT.append("INSERT INTO Osztaly (oszt, onev, ofo) VALUES ('%s', '%s', %s);" % (oszt, onev, ofo))

INSERT.append('')

# Diákok felsorolása
listDiak = {}
for sor in List[2].split('\n'):
    if len(sor) == 0 or sor[0] == '#': continue
    nev, uid, oszt, jelszo = sor.split(';')
    if listDiak.has_key(uid): Exit('Dupla diák azonosító: %s' % uid)
    if not listOsztaly.has_key(oszt): Exit('%s osztálya (%s) nem szerepel az osztályok közt!' % (nev, oszt));

    #jelszo = gen_password()

    OUT.append('%s;%s;%s' % (nev, listOsztaly[oszt], jelszo))
    INSERT.append("INSERT INTO Diak_base (id, jelszo, dnev, oszt) VALUES (%s, '%s', '%s', '%s');" % (uid, sha(jelszo), nev, oszt))


open(userFile + '.insert', 'w').write('\n'.join(INSERT))
open(userFile + '.pw', 'w').write('\n'.join(OUT))

# Admin jelszo generalasa
# Nem itt, kezzel egyszerübb :)
