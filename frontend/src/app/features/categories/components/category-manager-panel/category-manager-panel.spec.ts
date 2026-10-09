import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { CategoryManagerPanel } from './category-manager-panel';

describe('CategoryManagerPanel', () => {
  let component: CategoryManagerPanel;
  let fixture: ComponentFixture<CategoryManagerPanel>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CategoryManagerPanel],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();

    fixture = TestBed.createComponent(CategoryManagerPanel);
    component = fixture.componentInstance;
    fixture.componentRef.setInput('workspaceId', 1);
    fixture.componentRef.setInput('categories', []);
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
