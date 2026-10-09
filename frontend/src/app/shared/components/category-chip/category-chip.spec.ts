import { ComponentFixture, TestBed } from '@angular/core/testing';
import { CategoryDto } from '../../../core/models/category.models';
import { CategoryChip } from './category-chip';

describe('CategoryChip', () => {
  let component: CategoryChip;
  let fixture: ComponentFixture<CategoryChip>;

  const category: CategoryDto = {
    id: 1,
    name: 'Comida',
    icon: 'restaurant',
    color: '#FF9800',
    is_default: false,
    expenses_count: 3,
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CategoryChip],
    }).compileComponents();

    fixture = TestBed.createComponent(CategoryChip);
    fixture.componentRef.setInput('category', category);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('renders the category name and icon', () => {
    const text = fixture.nativeElement.textContent;
    expect(text).toContain('Comida');
    expect(text).toContain('restaurant');
  });
});
