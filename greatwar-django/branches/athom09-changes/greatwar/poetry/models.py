from eulcore.django.existdb.manager import Manager
from eulcore.django.existdb.models import XmlModel
from eulcore.xmlmap import XmlObject
from eulcore.xmlmap.fields import StringField, NodeField
from eulcore.xmlmap.teimap import Tei, TeiDiv, TEI_NAMESPACE

# TEI poetry models
# currently just slightly-modified versions of tei xmlmap objects



class PoetryBook(XmlModel, Tei):
    ROOT_NAMESPACES = {'tei' : TEI_NAMESPACE}
    objects = Manager('/tei:TEI')

class Poem(XmlModel, TeiDiv):
    ROOT_NAMESPACES = {'tei' : TEI_NAMESPACE}
    poet = StringField("tei:docAuthor/@n")
    nextdiv = NodeField("following::tei:div[@type='poem'][1]", "self")
    prevdiv = NodeField("preceding::tei:div[@type='poem'][1]", "self")
    objects = Manager('//tei:div')      # should this have [@type='poem'] ?

class Poet(XmlModel, XmlObject):
    ROOT_NAMESPACES = {'tei' : TEI_NAMESPACE}
    first_letter = StringField("substring(@n,1,1)")
    name  = StringField("@n")
    objects = Manager("//tei:div[@type='poem']/tei:docAuthor")
    