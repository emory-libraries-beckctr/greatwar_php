"""
Great War Poetry Test Cases
"""
from os import path

from django.conf import settings
from django.core.urlresolvers import reverse
from django.test import TestCase as DjangoTestCase

from eulcore import xmlmap
from eulcore.django.test import TestCase

from greatwar.poetry.models import PoetryBook, Poet, Poem, SourceDescription, \
    Bibliography


exist_fixture_path = path.join(path.dirname(path.abspath(__file__)), 'fixtures')
exist_index_path = path.join(path.dirname(path.abspath(__file__)), '..', 'collection.xconf')

# extend PoetryBook model
class TestPoetryBook(PoetryBook):
    poems = xmlmap.NodeListField('//tei:div[@type="poem"]', Poem)

class PoetryTestCase(DjangoTestCase):
    # tests for poetry model objects

    FIXTURES = ['flower.xml', 'fiery.xml', 'lest.xml']
    POET_STRING = '''<choice xmlns="http://www.tei-c.org/ns/1.0">
        <reg>Peterson, Margaret</reg>
    </choice>'''

    def setUp(self):
      
        # load the three xml poetry objects    
        self.poetry = dict()
        for file in self.FIXTURES:    
          filebase = file.split('.')[0]       
          self.poetry[filebase] = xmlmap.load_xmlobject_from_file(path.join(exist_fixture_path,
                                file), TestPoetryBook)
        # load the poet fixture docAuthor
        self.poet = xmlmap.load_xmlobject_from_string(self.POET_STRING, Poet)
        
                                  
    def test_init(self):
        for file, p in self.poetry.iteritems():   
            self.assert_(isinstance(p, PoetryBook))
          
    def test_xml_fixture_load(self):
        self.assertEqual(3, len(self.poetry))

    def test_poem_model(self):
    # TODO: test Poem object custom fields
    # may have to be dne using eXist...
        # reference to document-level info
        self.assertEqual('Flower of Youth: Poems in War Time, an electronic edition',
            self.poetry['flower'].poems[0].book.title)
        self.assertEqual('flower', self.poetry['flower'].poems[0].book.id)

      
    def test_poet_attributes(self):    
        self.assertEqual(self.poet.first_letter, 'P')
        self.assertEqual(self.poet.name, 'Peterson, Margaret')

    def test_dublin_core(self):
        # anthology - 2 editors, no author
        dc = self.poetry['fiery'].dublin_core
        self.assert_(isinstance(dc, xmlmap.dc.DublinCore))
        self.assertEqual('THE FIERY CROSS: An Anthology of War Poems', dc.title,
            'document title should be in dc:title')
        self.assertFalse(dc.creator_list, 'dc:creator should not be set TEI has no document author')
        self.assert_('Edwards, Mabel C.' in dc.contributor_list,
            'first editor should be included in dc:contributor')
        self.assert_('Booth, Mary' in dc.contributor_list,
            'secdon editor should be included in dc:contributor')
        self.assertEqual('Lewis H. Beck Center', dc.publisher,
            'publisher from teiHeader should be set in dc:publisher')
        self.assertEqual('2002', dc.date,
            'publication date from teiHeader should be set in dc:date')
        self.assert_('2002 Emory University. Permission is granted to download' in
            dc.rights, 'availability statement in dc:rights')
        self.assert_('Edwards, Mabel C. and Mary Booth' in dc.source,
            'source editors should be listed in dc:source')
        self.assert_('THE FIERY CROSS: An Anthology of War Poems' in dc.source,
            'source title should be listed in dc:source')
        self.assert_('London' in dc.source,
            'source publication location should be listed in dc:source')
        self.assert_('Grant Richards Ltd.' in dc.source,
            'source publisher should be listed in dc:source')
        self.assert_('1915' in dc.source,
            'source publication date should be listed in dc:source')
        # this fixture does not have LCSH subjects
        self.assertFalse(dc.subject_list)
        self.assert_('digital text is produced from a first-edition volume'
            in dc.description, 'encoding/project description should be in dc:description')

        self.assert_('Great Britain' in dc.coverage_list,
            'creation/rs[@type="geography"] should be in dc:coverage')
        self.assert_('1900-1999' in dc.coverage_list,
            'creation/date should be in dc:coverage')
        self.assert_('Women Writers Resource Project' in dc.relation,
            'teiHeader seriesStmt should be in dc:relation')

        # single-author volume, has LCSH subjects
        dc = self.poetry['flower'].dublin_core
        self.assertEqual('Tynan, Katharine, 1861-1931', dc.creator)
        self.assert_('World War, 1914-1918--Poetry.' in dc.subject_list)
        self.assert_('English poetry--Women authors--20th Century.' in dc.subject_list)
        self.assert_('War.' in dc.subject_list)

    def test_source_description(self):
        # check source/bibl mapping & types
        self.assert_(isinstance(self.poetry['flower'].source, SourceDescription))
        self.assert_(isinstance(self.poetry['flower'].source.bibl, Bibliography))
        # check formatted citation
        self.assertEqual('H. B. Elliot, ed. <i>Lest We Forget</i>. London: Jarrold & Sons, 1915.',
            self.poetry['lest'].source.citation())
        self.assertEqual('Edwards, Mabel C. and Mary Booth, ed. ' +
            '<i>THE FIERY CROSS: An Anthology of War Poems</i>. ' +
            'London: Grant Richards Ltd., 1915.',
            self.poetry['fiery'].source.citation())
        self.assertEqual('Tynan, Katharine. <i>Flower of Youth: Poems in War Time</i>. ' +
            'London: Sidgwick & Jackson, 1915.',
            self.poetry['flower'].source.citation())
        
        
class PoetryViewsTestCase(TestCase):
    # tests for ONLY those views that do NOT require eXist full-text index
    exist_fixtures = {'directory' : exist_fixture_path }
    
    def test_index(self):
        # poetry index should list all volumes loaded
        books_url = reverse('poetry:books')
        response = self.client.get(books_url)
        expected = 200
        self.assertEqual(response.status_code, expected,
                        'Expected %s but returned %s for %s' % \
                        (expected, response.status_code, books_url))
        # should contain title, author, link for each fixture
        self.assertContains(response, 'THE FIERY CROSS',
            msg_prefix='poetry index includes title of "The Fiery Cross"')
        self.assertContains(response, 'Mabel C. Edwards',
            msg_prefix='poetry index includes editor of "The Fiery Cross"')
        self.assertContains(response, reverse('poetry:book-toc', args=['fiery']),
            msg_prefix='poetry index includes link to "The Fiery Cross"')
        self.assertContains(response, 'Flower of Youth: Poems in War Time',
            msg_prefix='poetry index includes title of "Flower of Youth"')
        self.assertContains(response, 'Katharine Tynan',
            msg_prefix='poetry index includes author of "Flower of Youth"')
        self.assertContains(response, reverse('poetry:book-toc', args=['flower']),
            msg_prefix='poetry index includes link to "Flower of Youth"')
        self.assertContains(response, 'Lest We Forget',
            msg_prefix='poetry index includes title of "Lest we Forget')
        self.assertContains(response, 'H. B. Elliot',
            msg_prefix='poetry index includes editor of "Lest we Forget')
        self.assertContains(response, reverse('poetry:book-toc', args=['elliot']),
            msg_prefix='poetry index includes link to "Lest we Forget')

    def test_book_toc(self):
         # book toc should list all poems in a book
        book_toc_url = reverse('poetry:book-toc', args=['fiery'])
        response = self.client.get(book_toc_url)
        expected = 200
        self.assertEqual(response.status_code, expected,
                        'Expected %s but returned %s for %s' % \
                        (expected, response.status_code, book_toc_url))
        # should contain title, author, link for each poem
        # - first poem
        self.assertContains(response, 'For the Red Cross',
            msg_prefix='book ToC for fiery includes of "For the Red Cross" (first poem)')
        self.assertContains(response, 'Owen Seaman',
            msg_prefix='book ToC for fiery includes Owen Seaman,  author of ' +
                '"For the Red Cross" (first poem)')
        self.assertContains(response, reverse('poetry:poem', args=['fiery', 'fiery005']),
            msg_prefix='book ToC for fiery includes link to "For the Red Cross" (first poem)')
        # - poem in the middle somewhere
        self.assertContains(response, 'Gifts',      # currently ToC does not list second head
            msg_prefix='book ToC for fiery includes of "Gifts"')
        self.assertContains(response, 'Mary Booth',
            msg_prefix='book ToC for fiery includes Mary Booth,  author of Gifts')
        self.assertContains(response, reverse('poetry:poem', args=['fiery', 'fiery030']),
            msg_prefix='book ToC for fiery includes link to "Gifts"')
        # - last poem
        self.assertContains(response, u'Aux Po\xe8tes Futurs',
            msg_prefix='book ToC for fiery includes of "Aux Poetes Futurs" (last poem)')
        self.assertContains(response, 'Sully Prudhomme',
            msg_prefix='book ToC for fiery includes Sully Prudhomme,  author of ' +
                'Aux Poetes Futurs" (last poem)')
        self.assertContains(response, reverse('poetry:poem', args=['fiery', 'fiery069']),
            msg_prefix='book ToC for fiery includes link to "Aux Poetes Futurs" (last poem)')

        # - copyright info for book
        self.assertContains(response, '1915', # the date in the sourceDesc
            msg_prefix='source bibl for fiery contains "1915" (copyright date)')

        # toc for non-existent book should 404
        book_toc_url = reverse('poetry:book-toc', args=['nonexistent'])
        response = self.client.get(book_toc_url)
        expected = 404
        self.assertEqual(response.status_code, expected,
                        'Expected %s but returned %s for %s' % \
                        (expected, response.status_code, book_toc_url))

    def test_book_xml(self):
         # expose TEI xml for a book
        book_xml_url = reverse('poetry:book-xml', args=['fiery'])
        response = self.client.get(book_xml_url)
        expected = 200
        self.assertEqual(response.status_code, expected,
                        'Expected %s but returned %s for %s' % \
                        (expected, response.status_code, book_xml_url))
        self.assertEqual('application/tei+xml', response['Content-Type'])
        self.assertContains(response, '<TEI')

        # xml request for non-existent book should 404
        book_xml_url = reverse('poetry:book-toc', args=['nonexistent'])
        response = self.client.get(book_xml_url)
        expected = 404
        self.assertEqual(response.status_code, expected,
                        'Expected %s but returned %s for %s' % \
                        (expected, response.status_code, book_xml_url))

    def test_poem(self):
        poem_url = reverse('poetry:poem', args=['fiery', 'fiery005'])
        response = self.client.get(poem_url)
        expected = 200
        self.assertEqual(response.status_code, expected,
                        'Expected %s but returned %s for %s' % \
                        (expected, response.status_code, poem_url))

        self.assertContains(response, 'For the Red Cross',
            msg_prefix='response should contain poem title')
        self.assertContains(response, 'Owen Seaman',
            msg_prefix='response should contain poem author')
        self.assertContains(response, 'YE that have gentle hearts',
            msg_prefix='response should contain poem text (first line)')
        self.assertContains(response, 'Broke yesterday o\'erhead,',
            msg_prefix='response should contain poem text (middle line)')
        self.assertContains(response, 'The gate of life restored.',
            msg_prefix='response should contain poem text (last line)')
        self.assertContains(response, 'Reprinted by special permission of the proprietors of Punch.',
            msg_prefix='response should contain note text')

        # book title/link
        self.assertContains(response, 'THE FIERY CROSS',
            msg_prefix='response should contain book title')
        self.assertContains(response, reverse('poetry:book-toc', args=['fiery']),
            msg_prefix='response should contain link to book')
        # book citation
        self.assertContains(response, '1915', # the date in the sourceDesc
            msg_prefix='source bibl for fiery005 contains "1915" (book copyright date)')


        # previous
        self.assertContains(response, 'Previous poem',
            msg_prefix='response should contain link to previous poem if there is one')
        self.assertNotContains(response, 'Previous poem: None',
            msg_prefix='response should not display "None" when previous poem has no title')
        self.assertContains(response, reverse('poetry:poem', args=['fiery', 'fiery002']),
            msg_prefix='response should link to previous poem')
        # next
        self.assertContains(response, 'Next poem: From Germany',
            msg_prefix='response should contain link to next poem with title')        
        self.assertContains(response, reverse('poetry:poem', args=['fiery', 'fiery012']),
            msg_prefix='response should link to next poem')

        # first poem - no next link
        poem_url = reverse('poetry:poem', args=['fiery', 'fiery001'])
        response = self.client.get(poem_url)
        self.assertNotContains(response, 'Previous poem',
            msg_prefix='response should not link to previous poem when showing first poem')

        # last poem - no next link
        poem_url = reverse('poetry:poem', args=['fiery', 'fiery069'])
        response = self.client.get(poem_url)
        self.assertNotContains(response, 'Next poem',
            msg_prefix='response should not link to next poem when showing last poem')

        # not found
        poem_url = reverse('poetry:poem', args=['nonexistent', 'nonexistent01'])
        response = self.client.get(poem_url)
        expected = 404
        self.assertEqual(response.status_code, expected,
                        'Expected %s but returned %s for %s' % \
                        (expected, response.status_code, poem_url))



class FullTextPoetryViewsTest(TestCase):
    # tests for views that require eXist full-text index
    exist_fixtures = {'index' : settings.EXISTDB_INDEX_CONFIGFILE,
                       'directory' : exist_fixture_path }

    def test_view_search_keyword(self):
        search_url = reverse('poetry:search')

        # TODO: test/cleanup form display - shouldn't complain about no search terms,
        # 0 results when user hasn't entered a query

        response = self.client.get(search_url, {'keyword': 'spectre'})
        expected = 200
        self.assertEqual(response.status_code, expected,
                        'Expected %s but returned %s for %s' % \
                        (expected, response.status_code, search_url))
        
        self.assertContains(response, reverse('poetry:poem', kwargs={'doc_id':'fiery',
            'div_id': 'fiery012'}),
            msg_prefix='search results include link to poem with match (fiery012)')
        self.assertContains(response, 'From Germany',
            msg_prefix='search results include title of poem with match')
        self.assertContains(response, reverse('poetry:book-toc', kwargs={'doc_id':'fiery'}),
            msg_prefix='search results include link to book that contains poem with match')
        self.assertContains(response, 'Pale <span class="exist-match">spectre</span>',
            msg_prefix='search results include poem line with search term highlighted')

        # TODO: also matches fiery004 with title of 'None' - fix title display, test

        # exact phrase search - should work in poem line matches
        response = self.client.get(search_url, {'keyword': '"pale spectre"'})
        expected = 200
        self.assertEqual(response.status_code, expected,
                        'Expected %s but returned %s for %s' % \
                        (expected, response.status_code, search_url))
        self.assertContains(response, '<span class="exist-match">Pale spectre</span> of the slain',
            msg_prefix='search results include poem line with exact phrase search term highlighted')


    def test_view_search_title(self):
        search_url = reverse('poetry:search')
        response = self.client.get(search_url, {'title': 'Germany'})
        expected = 200
        self.assertEqual(response.status_code, expected, 'Expected %s but returned %s for %s' % \
                        (expected, response.status_code, search_url))
        # should include link to fiery012
        self.assertContains(response, reverse('poetry:poem',
                    kwargs={'doc_id':'fiery', 'div_id': 'fiery012'}),
            msg_prefix='search results include link to poem with match')

        # includes link to containing book
        self.assertContains(response, reverse('poetry:book-toc', kwargs={'doc_id':'fiery'}),
            msg_prefix='search results include link to book that contains poem with match')

        # correct title apears in search results
        self.assertContains(response, "From Germany")

    def test_view_search_author(self):
        search_url = reverse('poetry:search')
        response = self.client.get(search_url, {"author" : "Binyon"})
        expected = 200
        self.assertEqual(response.status_code, expected,
                        'Expected %s but returned %s for %s' % \
                        (expected, response.status_code, search_url))

        #Result 1
        #Should include link to fiery014
        self.assertContains(response, reverse('poetry:poem', kwargs={'doc_id':'fiery', 'div_id': 'fiery014'}),
            msg_prefix='search results include link to poem with match)')

        #includes link to containing book
        self.assertContains(response, reverse('poetry:book-toc', kwargs={'doc_id':'fiery'}),
            msg_prefix='search results include link to book that contains poem with match')

        #correct title apears in searh results
        self.assertContains(response, "The Cause")

        #Result 2
        #Should include link to elliott005
        self.assertContains(response, reverse('poetry:poem', kwargs={'doc_id':'elliot', 'div_id': 'elliott005'}),
            msg_prefix='search results include link to poem with match)')

        #includes link to containing book
        self.assertContains(response, reverse('poetry:book-toc', kwargs={'doc_id':'elliot'}),
            msg_prefix='search results include link to book that contains poem with match')

        #correct title apears in searh results
        self.assertContains(response, 'ODE')
